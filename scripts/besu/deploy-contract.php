<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

use App\Services\BesuRpcClient;
use App\Services\BesuTransactionSigner;
use Illuminate\Contracts\Console\Kernel;

if (! extension_loaded('gmp')) {
    fwrite(STDERR, "PHP GMP is required. On Windows, try: php -d extension=php_gmp.dll scripts/besu/deploy-contract.php\n");
    exit(1);
}

$application = require $root.'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

$artifact = $root.'/infra/besu/contracts/OrgChainAnchor.bin';
if (! is_file($artifact)) {
    throw new RuntimeException('Missing contract bytecode at '.$artifact.'.');
}

$rpc = app(BesuRpcClient::class);
$signer = app(BesuTransactionSigner::class);
$privateKey = $signer->privateKey();
$from = $signer->addressFromPrivateKey($privateKey);
$nonce = (string) $rpc->call('eth_getTransactionCount', [$from, 'pending']);
$gasPrice = (string) config('besu.gas_price_wei', '1');
$gasPrice = str_starts_with(strtolower($gasPrice), '0x')
    ? $gasPrice
    : '0x'.dechex(max(1, (int) $gasPrice));
$bytecode = trim((string) file_get_contents($artifact));

$signed = $signer->signLegacy([
    'nonce' => $nonce,
    'from' => $from,
    'gas' => (string) config('besu.deploy_gas_limit', '0x100000'),
    'gasPrice' => $gasPrice,
    'value' => '0x0',
    'data' => '0x'.$bytecode,
    'chainId' => (int) config('besu.chain_id', 20260920),
]);
$transactionHash = (string) $rpc->call('eth_sendRawTransaction', [$signed['raw_transaction']]);
$receipt = $rpc->waitForReceipt($transactionHash);
$contractAddress = (string) ($receipt['contractAddress'] ?? '');
if ($contractAddress === '') {
    throw new RuntimeException('Besu did not return a contract address for '.$transactionHash.'.');
}
if (strtolower((string) ($receipt['status'] ?? '0x0')) === '0x0') {
    throw new RuntimeException('Besu contract deployment reverted in transaction '.$transactionHash.'.');
}
$deployedCode = (string) $rpc->call('eth_getCode', [$contractAddress, 'latest']);
if ($deployedCode === '' || strtolower($deployedCode) === '0x') {
    throw new RuntimeException('Besu returned no runtime bytecode for '.$contractAddress.' after deployment.');
}

$manifest = [
    'contract_address' => strtolower($contractAddress),
    'deployment_transaction' => $transactionHash,
    'deployment_block_number' => $receipt['blockNumber'] ?? null,
    'chain_id' => (int) config('besu.chain_id', 20260920),
    'driver' => 'besu',
    'deployed_at' => date('c'),
];

$manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
$storageManifest = storage_path('app/besu/deployment.json');
if (! is_dir(dirname($storageManifest))) {
    mkdir(dirname($storageManifest), 0775, true);
}
file_put_contents($storageManifest, $manifestJson);

$networkManifest = $root.'/infra/besu/networkFiles/deployment.json';
if (! is_dir(dirname($networkManifest))) {
    mkdir(dirname($networkManifest), 0775, true);
}
file_put_contents($networkManifest, $manifestJson);

$networkFile = $root.'/infra/besu/networkFiles/network.json';
if (is_file($networkFile)) {
    $network = json_decode((string) file_get_contents($networkFile), true);
    if (is_array($network)) {
        $network['contract_address'] = strtolower($contractAddress);
        file_put_contents($networkFile, json_encode($network, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
}

fwrite(STDOUT, "OrgChainAnchor deployed successfully.\n");
fwrite(STDOUT, 'Contract: '.$contractAddress."\n");
fwrite(STDOUT, 'Transaction: '.$transactionHash."\n");
fwrite(STDOUT, 'Manifest: storage/app/besu/deployment.json'."\n");
