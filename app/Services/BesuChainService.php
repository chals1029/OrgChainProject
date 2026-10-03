<?php

namespace App\Services;

use App\VotingSystem\Core\Database as VotingDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use kornrunner\Keccak;

class BesuChainService
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(
        private readonly BesuRpcClient $rpc,
        private readonly BesuTransactionSigner $signer,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('besu.enabled', false);
    }

    /**
     * Anchor an existing application hash in the OrgChainAnchor contract.
     * The application keeps its SHA-256 record hash; Besu stores that hash as
     * a bytes32 event and as a durable on-chain lookup value.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function anchor(
        string $recordType,
        string $recordHash,
        string $referenceHash,
        array $metadata = [],
        string $previousHash = self::GENESIS_HASH,
    ): array {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Besu blockchain driver is not enabled.');
        }

        $contractAddress = $this->contractAddress();
        $privateKey = $this->signer->privateKey();
        $derivedAddress = $this->signer->addressFromPrivateKey($privateKey);
        $configuredAddress = strtolower(trim((string) config('besu.signer_address', '')));
        if ($configuredAddress !== '' && $configuredAddress !== strtolower($derivedAddress)) {
            throw new RuntimeException('BESU_SIGNER_ADDRESS does not match the configured private key.');
        }

        $nonce = (string) $this->rpc->call('eth_getTransactionCount', [$derivedAddress, 'pending']);
        $gasPrice = $this->gasPrice();
        $data = $this->anchorCallData($recordHash, $referenceHash, $recordType);

        $signed = $this->signer->signLegacy([
            'nonce' => $nonce,
            'from' => $derivedAddress,
            'to' => $contractAddress,
            'gas' => (string) config('besu.gas_limit', '0x493e0'),
            'gasPrice' => $gasPrice,
            'value' => '0x0',
            'data' => $data,
            'chainId' => (int) config('besu.chain_id', 20260920),
        ]);

        $transactionHash = (string) $this->rpc->call('eth_sendRawTransaction', [$signed['raw_transaction']]);
        $receipt = $this->rpc->waitForReceipt($transactionHash);

        if (isset($receipt['status']) && strtolower((string) $receipt['status']) === '0x0') {
            throw new RuntimeException('Besu rejected the anchor transaction '.$transactionHash.'.');
        }

        $blockNumber = BesuRpcClient::quantityToInt($receipt['blockNumber'] ?? 0);
        $sealedAt = (string) ($metadata['sealed_at'] ?? now()->toIso8601String());
        $confirmation = [
            'driver' => 'besu',
            'status' => 'ok',
            'transaction_hash' => $transactionHash,
            'chain_block_hash' => $receipt['blockHash'] ?? null,
            'chain_block_number' => $blockNumber,
            'contract_address' => $contractAddress,
            'validator_count' => (int) config('besu.validator_count', 4),
        ];

        return [
            'block_hash' => strtolower($this->stripHexPrefix($recordHash)),
            'previous_hash' => strtolower($this->stripHexPrefix($previousHash)),
            'index' => (int) ($metadata['index'] ?? 0),
            'nodes_confirmed' => (int) config('besu.validator_count', 4),
            'node_confirmations' => [$confirmation],
            'sealed_at' => $sealedAt,
            'chain_driver' => 'besu',
            'chain_tx_hash' => $transactionHash,
            'chain_block_number' => $blockNumber,
            'chain_contract_address' => $contractAddress,
            'chain_block_hash' => $receipt['blockHash'] ?? null,
        ];
    }

    /**
     * Verify a stored application hash against the on-chain lookup and its
     * transaction receipt.
     *
     * @return array<string, mixed>
     */
    public function verifyAnchor(string $recordHash, ?string $transactionHash = null): array
    {
        $this->signer->ensureDependencies();
        $nodes = [];
        $validatorCount = (int) config('besu.validator_count', 4);
        $recordHash = strtolower($this->stripHexPrefix(trim($recordHash)));

        try {
            $anchored = $this->rpc->call('eth_call', [[
                'to' => $this->contractAddress(),
                'data' => '0x'.substr(Keccak::hash('isAnchored(bytes32)', 256), 0, 8).str_pad($recordHash, 64, '0', STR_PAD_LEFT),
            ], 'latest']);
            $lookupOk = substr(strtolower((string) $anchored), -1) === '1';

            $receiptOk = true;
            $receipt = null;
            if ($transactionHash !== null && trim($transactionHash) !== '') {
                $receipt = $this->rpc->call('eth_getTransactionReceipt', [$transactionHash]);
                $receiptOk = is_array($receipt)
                    && strtolower((string) ($receipt['status'] ?? '0x0')) !== '0x0';
            }

            $ok = $lookupOk && $receiptOk;
            for ($node = 1; $node <= $validatorCount; $node++) {
                $nodes[] = [
                    'node' => $node,
                    'status' => $ok ? 'ok' : 'unverified',
                    'driver' => 'besu-qbft',
                ];
            }

            return [
                'ok' => $ok,
                'nodes_confirmed' => $ok ? $validatorCount : 0,
                'nodes' => $nodes,
                'message' => $ok
                    ? 'Besu QBFT anchor verified on-chain.'
                    : 'The application hash was not verified by the Besu anchor contract.',
                'transaction_hash' => $transactionHash,
                'receipt' => $receipt,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'nodes_confirmed' => 0,
                'nodes' => $nodes,
                'message' => 'Besu verification unavailable: '.$exception->getMessage(),
                'transaction_hash' => $transactionHash,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $receipt
     * @return array<string, mixed>
     */
    public function verifyVoteReceipt(array $receipt): array
    {
        $blockHash = (string) ($receipt['block_hash'] ?? '');
        if ($blockHash === '') {
            return [
                'ok' => false,
                'message' => 'This receipt has no chain seal (pre-chain ballot).',
                'receipt' => $receipt,
            ];
        }

        $anchor = $this->verifyAnchor($blockHash, $receipt['chain_tx_hash'] ?? null);
        $previousHash = (string) ($receipt['previous_hash'] ?? '');
        $expectedPrevious = self::GENESIS_HASH;

        try {
            $pdo = VotingDatabase::connection();
            $statement = $pdo->prepare(
                'SELECT block_hash FROM vote_receipts
                 WHERE election_id = :election_id AND id < :id
                   AND block_hash IS NOT NULL AND block_hash != ""
                 ORDER BY id DESC LIMIT 1'
            );
            $statement->execute([
                'election_id' => (int) ($receipt['election_id'] ?? 0),
                'id' => (int) ($receipt['id'] ?? 0),
            ]);
            $candidate = $statement->fetchColumn();
            if (is_string($candidate) && $candidate !== '') {
                $expectedPrevious = $candidate;
            }
        } catch (\Throwable) {
            // Keep the on-chain check useful even if the optional continuity query fails.
        }

        $linkOk = strtolower($this->stripHexPrefix($previousHash)) === strtolower($this->stripHexPrefix($expectedPrevious));
        $ok = (bool) ($anchor['ok'] ?? false) && $linkOk;

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'Ballot seal verified on the Besu QBFT network. Hash link is intact.'
                : 'Integrity check failed — the Besu anchor or hash link could not be verified.',
            'receipt' => [
                'reference_code' => $receipt['reference_code'] ?? null,
                'block_hash' => $blockHash,
                'previous_hash' => $previousHash,
                'ballot_root' => $receipt['ballot_root'] ?? null,
                'nodes_confirmed' => (int) ($receipt['nodes_confirmed'] ?? 0),
                'chain_tx_hash' => $receipt['chain_tx_hash'] ?? null,
            ],
            'nodes' => $anchor['nodes'] ?? [],
            'nodes_matched' => (int) ($anchor['nodes_confirmed'] ?? 0),
            'hash_link_ok' => $linkOk,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function voteChainStatus(int $electionId = 1): array
    {
        $totalBlocks = 0;
        $latestHash = self::GENESIS_HASH;
        try {
            $pdo = VotingDatabase::connection();
            $totalBlocks = (int) $pdo->query(
                'SELECT COUNT(*) FROM vote_receipts WHERE election_id = '.(int) $electionId.' AND block_hash IS NOT NULL AND block_hash != ""'
            )->fetchColumn();
            $latest = $pdo->query(
                'SELECT block_hash FROM vote_receipts WHERE election_id = '.(int) $electionId.' AND block_hash IS NOT NULL AND block_hash != "" ORDER BY id DESC LIMIT 1'
            )->fetchColumn();
            if (is_string($latest) && $latest !== '') {
                $latestHash = $latest;
            }
        } catch (\Throwable) {
            // Status still reports the network health when the optional count query fails.
        }

        try {
            $blockNumber = BesuRpcClient::quantityToInt($this->rpc->call('eth_blockNumber'));
            $chainId = BesuRpcClient::quantityToInt($this->rpc->call('eth_chainId'));
            $peerCount = BesuRpcClient::quantityToInt($this->rpc->call('net_peerCount'));
            $online = true;
        } catch (\Throwable) {
            $blockNumber = 0;
            $chainId = (int) config('besu.chain_id', 20260920);
            $peerCount = 0;
            $online = false;
        }

        return [
            'chain_name' => 'OrgChain Besu QBFT Vote Ledger',
            'election_id' => $electionId,
            'node_count' => (int) config('besu.validator_count', 4),
            'total_sealed_blocks' => $totalBlocks,
            'latest_block_hash' => $latestHash,
            'genesis_hash' => self::GENESIS_HASH,
            'nodes_health' => [
                'rpc' => $online ? 'online' : 'offline',
                'qbft_validators' => (int) config('besu.validator_count', 4),
                'peers' => $peerCount,
                'block_number' => $blockNumber,
            ],
            'consensus_algorithm' => 'Hyperledger Besu QBFT',
            'chain_id' => $chainId,
            'status' => $online ? 'Operational' : 'Unavailable',
        ];
    }

    public function contractAddress(): string
    {
        $configured = trim((string) config('besu.contract_address', ''));
        if ($configured !== '') {
            return $this->normalizeAddress($configured);
        }

        $deploymentFile = (string) config('besu.deployment_file', '');
        if ($deploymentFile !== '' && ! $this->isAbsolutePath($deploymentFile)) {
            $deploymentFile = base_path($deploymentFile);
        }
        if ($deploymentFile !== '' && is_file($deploymentFile)) {
            $deployment = json_decode((string) file_get_contents($deploymentFile), true);
            if (is_array($deployment) && ! empty($deployment['contract_address'])) {
                return $this->normalizeAddress((string) $deployment['contract_address']);
            }
        }

        throw new RuntimeException(
            'OrgChainAnchor is not configured. Run scripts/besu/deploy-contract.php and set BESU_CONTRACT_ADDRESS, or provide the deployment manifest.'
        );
    }

    private function anchorCallData(string $recordHash, string $referenceHash, string $recordType): string
    {
        $selector = substr(Keccak::hash('anchor(bytes32,bytes32,uint8)', 256), 0, 8);
        $type = hexdec(substr(hash('sha256', $recordType), 0, 2));

        return '0x'.$selector
            .str_pad(strtolower($this->stripHexPrefix(trim($recordHash))), 64, '0', STR_PAD_LEFT)
            .str_pad(strtolower($this->stripHexPrefix(trim($referenceHash))), 64, '0', STR_PAD_LEFT)
            .str_pad(dechex($type), 64, '0', STR_PAD_LEFT);
    }

    private function gasPrice(): string
    {
        $configured = trim((string) config('besu.gas_price_wei', '1'));
        if ($configured !== '') {
            if (str_starts_with(strtolower($configured), '0x')) {
                return $configured;
            }

            if (ctype_digit($configured)) {
                return '0x'.dechex(max(1, (int) $configured));
            }
        }

        $rpcPrice = (string) $this->rpc->call('eth_gasPrice');

        return strtolower($rpcPrice) === '0x0' ? '0x1' : $rpcPrice;
    }

    private function normalizeAddress(string $address): string
    {
        $address = strtolower(trim($address));
        if (! str_starts_with($address, '0x')) {
            $address = '0x'.$address;
        }
        if (! preg_match('/^0x[0-9a-f]{40}$/', $address)) {
            throw new RuntimeException('BESU_CONTRACT_ADDRESS is not a valid 20-byte Ethereum address.');
        }

        return $address;
    }

    private function stripHexPrefix(string $value): string
    {
        return str_starts_with(strtolower($value), '0x') ? substr($value, 2) : $value;
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('/^(?:[a-z]:[\\\\\/]|[\\\\\/]{1,2})/i', $path);
    }
}
