<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config = config('database.connections.mysql');
$directory = storage_path('app/private/backups/activity-budget-'.date('Ymd-His'));
Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
$process = new Symfony\Component\Process\Process([
    'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe',
    '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'],
    '--single-transaction', '--no-tablespaces', '--result-file='.$directory.'/database.sql',
    $config['database'],
], null, ['MYSQL_PWD' => $config['password']]);
$process->setTimeout(120);
$process->mustRun();
echo $directory.'/database.sql ('.filesize($directory.'/database.sql')." bytes)\n";
echo json_encode([
    'accounts' => App\Models\OrgFundAccount::query()->get(['organization_name', 'fiscal_year', 'total_funds'])->toArray(),
    'receipt_count' => App\Models\ExpenseReceiptReview::count(),
    'besu_enabled' => config('besu.enabled'),
], JSON_PRETTY_PRINT);
