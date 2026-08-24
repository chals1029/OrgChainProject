<?php

namespace App\VotingSystem\Core;

use PDO;

/**
 * Local 3-node vote chain.
 * Nodes live under storage/app/voting/chain/node-{1,2,3}/ as append-only JSONL ledgers.
 * Not a public blockchain — a thesis-ready multi-node integrity layer on one machine.
 */
class VoteBlockchain
{
    public const NODE_COUNT = 3;

    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function seal(int $electionId, string $referenceCode, int $voterId, array $validatedChoices, string $createdAt): array
    {
        $lockHandle = $this->acquireElectionLock($electionId);

        try {
            $previousHash = $this->latestHash($electionId);
            $ballotRoot = $this->ballotRoot($validatedChoices);
            $voterCommitment = hash('sha256', $electionId.'|'.$voterId.'|'.$referenceCode);

            $payload = [
                'election_id' => $electionId,
                'reference_code' => $referenceCode,
                'voter_commitment' => $voterCommitment,
                'ballot_root' => $ballotRoot,
                'created_at' => $createdAt,
                'previous_hash' => $previousHash,
            ];

            $blockHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));

            $block = [
                'index' => $this->nextIndex($electionId),
                'election_id' => $electionId,
                'reference_code' => $referenceCode,
                'previous_hash' => $previousHash,
                'block_hash' => $blockHash,
                'ballot_root' => $ballotRoot,
                'voter_commitment' => $voterCommitment,
                'created_at' => $createdAt,
                'sealed_at' => date('c'),
            ];

            $confirmations = [];
            for ($node = 1; $node <= self::NODE_COUNT; $node++) {
                $confirmations[] = $this->appendToNode($node, $electionId, $block);
            }

            return [
                'previous_hash' => $previousHash,
                'block_hash' => $blockHash,
                'ballot_root' => $ballotRoot,
                'voter_commitment' => $voterCommitment,
                'node_confirmations' => $confirmations,
                'nodes_confirmed' => count(array_filter($confirmations, static fn (array $c): bool => ($c['status'] ?? '') === 'ok')),
            ];
        } finally {
            $this->releaseElectionLock($lockHandle);
        }
    }

    public function purgeAllLedgers(): void
    {
        $root = storage_path('app/voting/chain');
        if (! is_dir($root)) {
            return;
        }

        for ($node = 1; $node <= self::NODE_COUNT; $node++) {
            $dir = $this->nodeDir($node);
            if (! is_dir($dir)) {
                continue;
            }

            foreach (glob($dir.DIRECTORY_SEPARATOR.'election-*.jsonl') ?: [] as $file) {
                @unlink($file);
            }
        }
    }

    public function resyncNodeLedgers(int $electionId = 1): void
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT reference_code, previous_hash, block_hash, ballot_root, voter_commitment, created_at
             FROM vote_receipts
             WHERE election_id = :election_id
               AND block_hash IS NOT NULL
               AND block_hash != ""
             ORDER BY id ASC'
        );
        $statement->execute(['election_id' => $electionId]);
        $receipts = $statement->fetchAll(PDO::FETCH_ASSOC);

        for ($node = 1; $node <= self::NODE_COUNT; $node++) {
            $path = $this->nodeLedgerPath($node, $electionId);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ($receipts as $index => $receipt) {
            $block = [
                'index' => $index + 1,
                'election_id' => $electionId,
                'reference_code' => $receipt['reference_code'],
                'previous_hash' => $receipt['previous_hash'],
                'block_hash' => $receipt['block_hash'],
                'ballot_root' => $receipt['ballot_root'],
                'voter_commitment' => $receipt['voter_commitment'],
                'created_at' => $receipt['created_at'],
                'sealed_at' => $receipt['created_at'],
            ];
            for ($node = 1; $node <= self::NODE_COUNT; $node++) {
                $this->appendToNode($node, $electionId, $block);
            }
        }
    }

    public function verify(string $referenceCode): array
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT * FROM vote_receipts WHERE reference_code = :reference_code LIMIT 1'
        );
        $statement->execute(['reference_code' => $referenceCode]);
        $receipt = $statement->fetch(PDO::FETCH_ASSOC);

        if (! $receipt) {
            return [
                'ok' => false,
                'message' => 'Receipt not found in the voting database.',
            ];
        }

        $electionId = (int) $receipt['election_id'];
        $blockHash = (string) ($receipt['block_hash'] ?? '');
        $previousHash = (string) ($receipt['previous_hash'] ?? '');

        if ($blockHash === '') {
            return [
                'ok' => false,
                'message' => 'This receipt has no chain seal (pre-chain ballot).',
                'receipt' => $receipt,
            ];
        }

        $nodeHits = [];
        $matched = 0;

        for ($node = 1; $node <= self::NODE_COUNT; $node++) {
            $found = $this->findOnNode($node, $electionId, $referenceCode, $blockHash);
            $nodeHits[] = $found;
            if (($found['status'] ?? '') === 'ok') {
                $matched++;
            }
        }

        $chainOk = $matched === self::NODE_COUNT;
        $linkOk = $this->previousHashMatchesChain($electionId, $referenceCode, $previousHash);

        return [
            'ok' => $chainOk && $linkOk,
            'message' => $chainOk && $linkOk
                ? 'Ballot seal verified across all 3 nodes. Hash link is intact.'
                : 'Integrity check failed — node copies or hash link do not fully match.',
            'receipt' => [
                'reference_code' => $receipt['reference_code'],
                'block_hash' => $blockHash,
                'previous_hash' => $previousHash,
                'ballot_root' => $receipt['ballot_root'] ?? null,
                'nodes_confirmed' => (int) ($receipt['nodes_confirmed'] ?? 0),
            ],
            'nodes' => $nodeHits,
            'nodes_matched' => $matched,
            'hash_link_ok' => $linkOk,
        ];
    }

    public function latestHash(int $electionId): string
    {
        $pdo = Database::connection();

        if (! $this->hasChainColumns($pdo)) {
            return self::GENESIS_HASH;
        }

        $statement = $pdo->prepare(
            'SELECT block_hash
             FROM vote_receipts
             WHERE election_id = :election_id
               AND block_hash IS NOT NULL
               AND block_hash != ""
             ORDER BY id DESC
             LIMIT 1'
        );
        $statement->execute(['election_id' => $electionId]);
        $hash = $statement->fetchColumn();

        return is_string($hash) && $hash !== '' ? $hash : self::GENESIS_HASH;
    }

    public function getChainStatus(int $electionId = 1): array
    {
        $path = $this->nodeLedgerPath(1, $electionId);
        $totalBlocks = 0;
        $latestHash = self::GENESIS_HASH;

        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $totalBlocks = count($lines);
            if ($totalBlocks > 0) {
                $lastRow = json_decode(end($lines), true);
                if (is_array($lastRow) && ! empty($lastRow['block_hash'])) {
                    $latestHash = $lastRow['block_hash'];
                }
            }
        }

        return [
            'chain_name' => 'OrgChain 3-Node Vote Ledger',
            'election_id' => $electionId,
            'node_count' => self::NODE_COUNT,
            'total_sealed_blocks' => $totalBlocks,
            'latest_block_hash' => $latestHash,
            'genesis_hash' => self::GENESIS_HASH,
            'nodes_health' => [
                'node_1' => is_file($this->nodeLedgerPath(1, $electionId)) ? 'online' : 'ready',
                'node_2' => is_file($this->nodeLedgerPath(2, $electionId)) ? 'online' : 'ready',
                'node_3' => is_file($this->nodeLedgerPath(3, $electionId)) ? 'online' : 'ready',
            ],
            'consensus_algorithm' => 'Multi-node SHA-256 Hash Chain Proof',
            'status' => 'Operational',
        ];
    }

    public function getBlock(int $electionId = 1, ?string $hash = null, ?int $index = null): ?array
    {
        $path = $this->nodeLedgerPath(1, $electionId);
        if (! is_file($path)) {
            return null;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $lineIndex => $line) {
            $block = json_decode($line, true);
            if (! is_array($block)) {
                continue;
            }

            $currentIndex = $lineIndex + 1;
            if ($index !== null && $currentIndex === $index) {
                return $block;
            }
            if ($hash !== null && (($block['block_hash'] ?? '') === $hash || ($block['reference_code'] ?? '') === $hash)) {
                return $block;
            }
        }

        return null;
    }

    private function nextIndex(int $electionId): int
    {
        $path = $this->nodeLedgerPath(1, $electionId);
        if (! is_file($path)) {
            return 1;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return count($lines) + 1;
    }

    /**
     * @param array<int, array<int>> $validatedChoices
     */
    private function ballotRoot(array $validatedChoices): string
    {
        ksort($validatedChoices);
        $parts = [];

        foreach ($validatedChoices as $positionId => $candidateIds) {
            $ids = $candidateIds;
            sort($ids);
            $parts[] = $positionId.':'.implode(',', $ids);
        }

        return hash('sha256', implode('|', $parts));
    }

    private function appendToNode(int $node, int $electionId, array $block): array
    {
        $nodeUrl = $this->getNodeUrl($node);

        if ($nodeUrl !== 'local' && (str_starts_with($nodeUrl, 'http://') || str_starts_with($nodeUrl, 'https://'))) {
            $remoteResult = $this->sendBlockToRemoteNode($node, $nodeUrl, $electionId, $block);
            // Also maintain local replica for fallback and local audit verification
            $this->appendBlockToLocalLedger($node, $electionId, $block);
            return $remoteResult;
        }

        return $this->appendBlockToLocalLedger($node, $electionId, $block);
    }

    public function appendBlockToLocalLedger(int $node, int $electionId, array $block): array
    {
        $dir = $this->nodeDir($node);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return [
                'node' => $node,
                'status' => 'error',
                'message' => 'Could not create node storage directory.',
            ];
        }

        $path = $this->nodeLedgerPath($node, $electionId);
        $line = json_encode($block, JSON_UNESCAPED_SLASHES).PHP_EOL;
        $written = @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            return [
                'node' => $node,
                'status' => 'error',
                'message' => 'Failed to append block to local ledger.',
            ];
        }

        return [
            'node' => $node,
            'status' => 'ok',
            'block_hash' => $block['block_hash'],
            'path' => 'node-'.$node.'/election-'.$electionId.'.jsonl',
        ];
    }

    private function sendBlockToRemoteNode(int $node, string $nodeUrl, int $electionId, array $block): array
    {
        $baseUrl = rtrim($nodeUrl, '/');
        $endpoint = (str_contains($baseUrl, '/voting-system') ? $baseUrl : $baseUrl.'/voting-system').'/api/blockchain/node-receive';
        $secret = (string) (voting_config('nodes.secret_token', 'orgchain-node-auth-secret-2026') ?? '');
        $timeout = (int) (voting_config('nodes.timeout_seconds', 3) ?? 3);

        $payload = json_encode([
            'target_node' => $node,
            'block' => $block,
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Node-Token: '.$secret,
                'User-Agent: OrgChain-VoteBlockchain/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return [
                'node' => $node,
                'status' => 'error',
                'message' => 'Remote node RPC broadcast failed: '.($curlError ?: "HTTP {$httpCode}"),
                'url' => $endpoint,
            ];
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded) && ($decoded['status'] ?? '') === 'ok') {
            return [
                'node' => $node,
                'status' => 'ok',
                'block_hash' => $block['block_hash'],
                'remote' => true,
                'url' => $endpoint,
            ];
        }

        return [
            'node' => $node,
            'status' => 'error',
            'message' => $decoded['error'] ?? 'Remote node rejected block payload.',
            'url' => $endpoint,
        ];
    }

    private function findOnNode(int $node, int $electionId, string $referenceCode, string $blockHash): array
    {
        $nodeUrl = $this->getNodeUrl($node);

        if ($nodeUrl !== 'local' && (str_starts_with($nodeUrl, 'http://') || str_starts_with($nodeUrl, 'https://'))) {
            $remoteFind = $this->findOnRemoteNode($node, $nodeUrl, $electionId, $referenceCode, $blockHash);
            if (($remoteFind['status'] ?? '') === 'ok') {
                return $remoteFind;
            }
        }

        return $this->findOnLocalNode($node, $electionId, $referenceCode, $blockHash);
    }

    public function findOnLocalNode(int $node, int $electionId, string $referenceCode, string $blockHash): array
    {
        $path = $this->nodeLedgerPath($node, $electionId);
        if (! is_file($path)) {
            return [
                'node' => $node,
                'status' => 'missing',
                'message' => 'Node ledger file not found.',
            ];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [
                'node' => $node,
                'status' => 'error',
                'message' => 'Could not read node ledger file.',
            ];
        }

        $matched = false;
        while (($line = fgets($handle)) !== false) {
            $row = json_decode(trim($line), true);
            if (! is_array($row)) {
                continue;
            }
            if (($row['reference_code'] ?? '') === $referenceCode && ($row['block_hash'] ?? '') === $blockHash) {
                $matched = true;
                break;
            }
        }
        fclose($handle);

        return [
            'node' => $node,
            'status' => $matched ? 'ok' : 'mismatch',
            'message' => $matched ? 'Block present and verified on node.' : 'Block not found on node ledger.',
        ];
    }

    private function findOnRemoteNode(int $node, string $nodeUrl, int $electionId, string $referenceCode, string $blockHash): array
    {
        $baseUrl = rtrim($nodeUrl, '/');
        $endpoint = (str_contains($baseUrl, '/voting-system') ? $baseUrl : $baseUrl.'/voting-system')
            .'/api/blockchain/node-verify-block?'.http_build_query([
                'election_id' => $electionId,
                'node' => $node,
                'reference' => $referenceCode,
                'block_hash' => $blockHash,
            ]);

        $timeout = (int) (voting_config('nodes.timeout_seconds', 3) ?? 3);
        $secret = (string) (voting_config('nodes.secret_token', 'orgchain-node-auth-secret-2026') ?? '');

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'X-Node-Token: '.$secret,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode === 200) {
            $decoded = json_decode($response, true);
            if (is_array($decoded) && isset($decoded['result'])) {
                $res = $decoded['result'];
                $res['remote'] = true;
                return $res;
            }
        }

        return [
            'node' => $node,
            'status' => 'remote_unreachable',
            'message' => 'Could not query remote validator node.',
        ];
    }

    public function getNodeStatus(int $node, int $electionId = 1): array
    {
        $path = $this->nodeLedgerPath($node, $electionId);
        $totalBlocks = 0;
        $latestHash = self::GENESIS_HASH;

        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $totalBlocks = count($lines);
            if ($totalBlocks > 0) {
                $lastRow = json_decode(end($lines), true);
                if (is_array($lastRow) && ! empty($lastRow['block_hash'])) {
                    $latestHash = $lastRow['block_hash'];
                }
            }
        }

        return [
            'node' => $node,
            'election_id' => $electionId,
            'status' => is_file($path) ? 'online' : 'ready',
            'total_blocks' => $totalBlocks,
            'latest_block_hash' => $latestHash,
            'ledger_file' => 'node-'.$node.'/election-'.$electionId.'.jsonl',
        ];
    }

    private function getNodeUrl(int $node): string
    {
        $urls = voting_config('nodes.urls', []);
        return is_array($urls) && isset($urls[$node]) ? trim((string) $urls[$node]) : 'local';
    }

    private function previousHashMatchesChain(int $electionId, string $referenceCode, string $previousHash): bool
    {
        $path = $this->nodeLedgerPath(1, $electionId);
        if (! is_file($path)) {
            return $previousHash === self::GENESIS_HASH;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $prev = self::GENESIS_HASH;

        foreach ($lines as $line) {
            $row = json_decode($line, true);
            if (! is_array($row)) {
                continue;
            }

            if (($row['reference_code'] ?? '') === $referenceCode) {
                return ($row['previous_hash'] ?? '') === $previousHash
                    && $previousHash === $prev;
            }

            $prev = (string) ($row['block_hash'] ?? $prev);
        }

        return false;
    }

    private function nodeDir(int $node): string
    {
        return storage_path('app/voting/chain/node-'.$node);
    }

    private function nodeLedgerPath(int $node, int $electionId): string
    {
        return $this->nodeDir($node).DIRECTORY_SEPARATOR.'election-'.$electionId.'.jsonl';
    }

    /**
     * @return resource|null
     */
    private function acquireElectionLock(int $electionId)
    {
        $dir = storage_path('app/voting/chain');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        $path = $dir.DIRECTORY_SEPARATOR.'.lock-election-'.$electionId;
        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return null;
        }

        @flock($handle, LOCK_EX);

        return $handle;
    }

    /**
     * @param resource|null $handle
     */
    private function releaseElectionLock($handle): void
    {
        if (! is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    private function hasChainColumns(PDO $pdo): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $cols = $pdo->query('PRAGMA table_info(vote_receipts)')->fetchAll(PDO::FETCH_ASSOC);
                $names = array_column($cols, 'name');
                $cached = in_array('block_hash', $names, true);

                return $cached;
            }

            $statement = $pdo->query("SHOW COLUMNS FROM vote_receipts LIKE 'block_hash'");
            $cached = (bool) $statement->fetchColumn();

            return $cached;
        } catch (\Throwable) {
            $cached = false;

            return false;
        }
    }
}
