<?php

namespace App\Services;

/**
 * Permissioned 3-node hash chain for budget utilization / expense seals.
 * Ledgers: storage/app/orgchain/budget/node-{1,2,3}/budget.jsonl
 */
class BudgetChainService
{
    public const NODE_COUNT = 3;

    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * @param  array<string, mixed>  $payload
     * @return array{block_hash: string, previous_hash: string, index: int, nodes_confirmed: int, node_confirmations: list<array<string, mixed>>, sealed_at: string}
     */
    public function sealExpense(array $payload): array
    {
        $lock = $this->acquireLock();

        try {
            $previousHash = $this->latestHash();
            $index = $this->nextIndex();
            $sealedAt = now()->toIso8601String();

            $core = [
                'type' => 'budget_utilization',
                'index' => $index,
                'previous_hash' => $previousHash,
                'activity_title' => (string) ($payload['activity_title'] ?? ''),
                'item_name' => (string) ($payload['item_name'] ?? ''),
                'supplier' => (string) ($payload['supplier'] ?? ''),
                'organization_name' => (string) ($payload['organization_name'] ?? ''),
                'receipt_reference' => (string) ($payload['receipt_reference'] ?? ''),
                'quantity' => (int) ($payload['quantity'] ?? 1),
                'unit_cost' => (float) ($payload['unit_cost'] ?? 0),
                'total' => (float) ($payload['total'] ?? (($payload['quantity'] ?? 1) * ($payload['unit_cost'] ?? 0))),
                'expense_date' => (string) ($payload['expense_date'] ?? ''),
                'sealed_at' => $sealedAt,
            ];

            $blockHash = hash('sha256', json_encode($core, JSON_UNESCAPED_SLASHES));
            $block = array_merge($core, ['block_hash' => $blockHash]);

            $confirmations = [];
            for ($node = 1; $node <= self::NODE_COUNT; $node++) {
                $confirmations[] = $this->appendToNode($node, $block);
            }

            return [
                'block_hash' => $blockHash,
                'previous_hash' => $previousHash,
                'index' => $index,
                'nodes_confirmed' => count(array_filter($confirmations, static fn (array $c): bool => ($c['status'] ?? '') === 'ok')),
                'node_confirmations' => $confirmations,
                'sealed_at' => $sealedAt,
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentBlocks(int $limit = 8): array
    {
        $path = $this->nodeLedgerPath(1);
        if (! is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $blocks = [];
        foreach (array_reverse($lines) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $blocks[] = $decoded;
            }
            if (count($blocks) >= $limit) {
                break;
            }
        }

        return $blocks;
    }

    public function verifyHash(string $blockHash): array
    {
        $found = [];
        for ($node = 1; $node <= self::NODE_COUNT; $node++) {
            $path = $this->nodeLedgerPath($node);
            $ok = false;
            if (is_file($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $decoded = json_decode($line, true);
                    if (is_array($decoded) && ($decoded['block_hash'] ?? '') === $blockHash) {
                        $ok = true;
                        break;
                    }
                }
            }
            $found[] = ['node' => $node, 'status' => $ok ? 'ok' : 'missing'];
        }

        $confirmed = count(array_filter($found, static fn (array $r): bool => $r['status'] === 'ok'));

        return [
            'ok' => $confirmed === self::NODE_COUNT,
            'nodes_confirmed' => $confirmed,
            'nodes' => $found,
            'message' => $confirmed === self::NODE_COUNT
                ? 'Budget seal verified across all 3 nodes.'
                : 'Budget seal incomplete ('.$confirmed.'/'.self::NODE_COUNT.' nodes).',
        ];
    }

    private function latestHash(): string
    {
        $path = $this->nodeLedgerPath(1);
        if (! is_file($path)) {
            return self::GENESIS_HASH;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if ($lines === []) {
            return self::GENESIS_HASH;
        }

        $last = json_decode((string) end($lines), true);

        return is_array($last) ? (string) ($last['block_hash'] ?? self::GENESIS_HASH) : self::GENESIS_HASH;
    }

    private function nextIndex(): int
    {
        $path = $this->nodeLedgerPath(1);
        if (! is_file($path)) {
            return 1;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return count($lines) + 1;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array{node: int, status: string, message?: string, block_hash?: string}
     */
    private function appendToNode(int $node, array $block): array
    {
        $dir = $this->nodeDir($node);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return ['node' => $node, 'status' => 'error', 'message' => 'Could not create budget node directory.'];
        }

        $path = $this->nodeLedgerPath($node);
        $written = @file_put_contents($path, json_encode($block, JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            return ['node' => $node, 'status' => 'error', 'message' => 'Failed to append budget block.'];
        }

        return [
            'node' => $node,
            'status' => 'ok',
            'block_hash' => $block['block_hash'],
        ];
    }

    private function nodeDir(int $node): string
    {
        return storage_path('app/orgchain/budget/node-'.$node);
    }

    private function nodeLedgerPath(int $node): string
    {
        return $this->nodeDir($node).DIRECTORY_SEPARATOR.'budget.jsonl';
    }

    /** @return resource|false */
    private function acquireLock()
    {
        $dir = storage_path('app/orgchain/budget');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $handle = @fopen($dir.DIRECTORY_SEPARATOR.'.lock', 'c+');
        if ($handle) {
            flock($handle, LOCK_EX);
        }

        return $handle;
    }

    /** @param resource|false $handle */
    private function releaseLock($handle): void
    {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
