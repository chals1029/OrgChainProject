<?php

// Read-only operational check. Does not submit transactions or change records.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = app(App\Services\ActivityBudgetService::class);
$year = $service->period()['academic_year'];
$rows = App\Models\ExpenseReceiptReview::all();
$result = [
    'academic_year' => $year,
    'fund_accounts_current_year' => App\Models\OrgFundAccount::where('fiscal_year', $year)->count(),
    'receipt_count' => $rows->count(),
    'unlinked_legacy_receipts' => $rows->whereNull('org_activity_id')->count(),
    'missing_receipt_originals' => $rows->filter(fn ($r) => !Illuminate\Support\Facades\Storage::disk($r->receipt_disk ?: 'public')->exists($r->receipt_path))->count(),
    'pending_seals' => $rows->where('verification_status', 'pending_seal')->count(),
    'blockchain_driver' => config('besu.driver'),
];
if (config('besu.enabled')) {
    try {
        $rpc = app(App\Services\BesuRpcClient::class);
        $result['besu_reachable'] = $rpc->isAvailable();
        $result['block_number'] = App\Services\BesuRpcClient::quantityToInt($rpc->call('eth_blockNumber'));
        $result['peers'] = App\Services\BesuRpcClient::quantityToInt($rpc->call('net_peerCount'));
        $result['validators'] = count($rpc->call('qbft_getValidatorsByBlockNumber', ['latest']));
        $sample = $rows->where('chain_driver', 'besu')->whereNotNull('chain_tx_hash')->last();
        if ($sample) {
            $verified = app(App\Services\BudgetChainService::class)->verifyHash($sample->chain_hash);
            $result['existing_receipt_anchor_verified'] = $verified['ok'] ?? false;
        }
    } catch (Throwable $e) {
        $result['blockchain_check_error'] = $e->getMessage();
    }
}
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
