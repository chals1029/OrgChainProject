<?php

namespace Tests\Unit;

use App\Services\BudgetChainService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BudgetChainServiceTest extends TestCase
{
    private string $budgetRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->budgetRoot = storage_path('app/orgchain/budget');
        File::deleteDirectory($this->budgetRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->budgetRoot);
        parent::tearDown();
    }

    public function test_seal_expense_writes_three_nodes_and_links_hashes(): void
    {
        $service = new BudgetChainService;

        $first = $service->sealExpense([
            'activity_title' => 'Unit Test Activity',
            'item_name' => 'Test Supplies',
            'receipt_reference' => 'OR-UNIT-001',
            'quantity' => 2,
            'unit_cost' => 100,
            'total' => 200,
            'expense_date' => '2026-09-18',
        ]);

        $this->assertSame(3, $first['nodes_confirmed']);
        $this->assertSame(BudgetChainService::GENESIS_HASH, $first['previous_hash']);
        $this->assertNotEmpty($first['block_hash']);

        foreach ([1, 2, 3] as $node) {
            $path = storage_path("app/orgchain/budget/node-{$node}/budget.jsonl");
            $this->assertFileExists($path);
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $this->assertCount(1, $lines);
        }

        $second = $service->sealExpense([
            'activity_title' => 'Unit Test Activity',
            'item_name' => 'More Supplies',
            'receipt_reference' => 'OR-UNIT-002',
            'quantity' => 1,
            'unit_cost' => 50,
            'total' => 50,
            'expense_date' => '2026-09-18',
        ]);

        $this->assertSame($first['block_hash'], $second['previous_hash']);
        $this->assertSame(2, $second['index']);

        $verify = $service->verifyHash($second['block_hash']);
        $this->assertTrue($verify['ok']);
        $this->assertSame(3, $verify['nodes_confirmed']);
    }

    public function test_recent_blocks_returns_newest_first(): void
    {
        $service = new BudgetChainService;
        $service->sealExpense(['item_name' => 'A', 'quantity' => 1, 'unit_cost' => 1, 'total' => 1]);
        $service->sealExpense(['item_name' => 'B', 'quantity' => 1, 'unit_cost' => 1, 'total' => 1]);

        $blocks = $service->recentBlocks(5);
        $this->assertGreaterThanOrEqual(2, count($blocks));
        $this->assertSame('B', $blocks[0]['item_name']);
    }
}
