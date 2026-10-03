<?php

declare(strict_types=1);

$autoload = require dirname(__DIR__, 2).'/vendor/autoload.php';
if ($autoload instanceof Composer\Autoload\ClassLoader) {
    $autoload->addPsr4('Elliptic\\', dirname(__DIR__, 2).'/vendor/simplito/elliptic-php/lib');
    $autoload->addPsr4('BN\\', dirname(__DIR__, 2).'/vendor/simplito/bn-php/lib');
    $autoload->addPsr4('BI\\', dirname(__DIR__, 2).'/vendor/simplito/bigint-wrapper-php/lib');
    $autoload->addPsr4('kornrunner\\', dirname(__DIR__, 2).'/vendor/kornrunner/keccak/src');
}

use Elliptic\EC;
use kornrunner\Keccak;

if (! extension_loaded('gmp')) {
    fwrite(STDERR, "PHP GMP is required. On Windows, try: php -d extension=php_gmp.dll scripts/besu/generate-network.php\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
$output = $root.DIRECTORY_SEPARATOR.'infra'.DIRECTORY_SEPARATOR.'besu'.DIRECTORY_SEPARATOR.'networkFiles';
$chainId = 20260920;

$privateKeys = [
    'node-1' => str_pad(dechex(1), 64, '0', STR_PAD_LEFT),
    'node-2' => str_pad(dechex(2), 64, '0', STR_PAD_LEFT),
    'node-3' => str_pad(dechex(3), 64, '0', STR_PAD_LEFT),
    'node-4' => str_pad(dechex(4), 64, '0', STR_PAD_LEFT),
    'app' => str_pad(dechex(0x101), 64, '0', STR_PAD_LEFT),
];

$accounts = [];
foreach ($privateKeys as $name => $privateKey) {
    $publicKey = publicKeyFromPrivateKey($privateKey);
    $address = addressFromPublicKey($publicKey);
    $accounts[$name] = [
        'private_key' => $privateKey,
        'public_key' => $publicKey,
        'address' => $address,
    ];

    $directory = $output.DIRECTORY_SEPARATOR.$name;
    ensureDirectory($directory);
    writeFile($directory.DIRECTORY_SEPARATOR.'key', $privateKey.PHP_EOL);
    writeFile($directory.DIRECTORY_SEPARATOR.'key.pub', $publicKey.PHP_EOL);
    if (str_starts_with($name, 'node-')) {
        ensureDirectory($output.DIRECTORY_SEPARATOR.$name.'-data-runtime');
    }
}

$validators = array_map(static fn (string $name): string => $accounts[$name]['address'], [
    'node-1', 'node-2', 'node-3', 'node-4',
]);

$extraData = '0x'.bin2hex(rlpEncode([
    str_repeat("\0", 32),
    array_map(static fn (string $address): string => hex2bin(stripHexPrefix($address)), $validators),
    [],
    '',
    [],
]));

$richBalance = '0x3635c9adc5dea000000';
$alloc = [];
foreach (array_values($accounts) as $account) {
    $alloc[stripHexPrefix($account['address'])] = ['balance' => $richBalance];
}

$genesis = [
    'config' => [
        'chainid' => $chainId,
        'berlinBlock' => 0,
        'londonBlock' => 0,
        'qbft' => [
            'epochlength' => 30000,
            'blockperiodseconds' => 2,
            'requesttimeoutseconds' => 4,
        ],
    ],
    'nonce' => '0x0',
    'timestamp' => '0x'.dechex(time()),
    'extraData' => $extraData,
    'gasLimit' => '0x1c9c380',
    'baseFeePerGas' => '0x0',
    'difficulty' => '0x1',
    'mixHash' => '0x63746963616c2062797a616e74696e65206661756c7420746f6c6572616e6365',
    'coinbase' => '0x0000000000000000000000000000000000000000',
    'alloc' => $alloc,
    'number' => '0x0',
    'gasUsed' => '0x0',
    'parentHash' => '0x'.str_repeat('0', 64),
];

writeFile(
    $output.DIRECTORY_SEPARATOR.'genesis.json',
    json_encode($genesis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
);

$network = [
    'chain_id' => $chainId,
    'consensus' => 'QBFT',
    'validator_count' => count($validators),
    'validator_addresses' => $validators,
    'app_address' => $accounts['app']['address'],
    'rpc_url' => 'http://127.0.0.1:8545',
    'contract_address' => null,
    'signer_private_key_file' => 'infra/besu/networkFiles/app/key',
];
writeFile(
    $output.DIRECTORY_SEPARATOR.'network.json',
    json_encode($network, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
);

$composeEnv = [
    'BESU_CHAIN_ID='.$chainId,
    'NODE1_PUBLIC_KEY='.$accounts['node-1']['public_key'],
    'NODE2_PUBLIC_KEY='.$accounts['node-2']['public_key'],
    'NODE3_PUBLIC_KEY='.$accounts['node-3']['public_key'],
    'NODE4_PUBLIC_KEY='.$accounts['node-4']['public_key'],
    'APP_ADDRESS='.$accounts['app']['address'],
];
writeFile(
    $root.DIRECTORY_SEPARATOR.'infra'.DIRECTORY_SEPARATOR.'besu'.DIRECTORY_SEPARATOR.'.env',
    implode(PHP_EOL, $composeEnv).PHP_EOL
);

fwrite(STDOUT, "Generated a four-validator QBFT network.\n");
fwrite(STDOUT, 'App signer address: '.$accounts['app']['address']."\n");
fwrite(STDOUT, 'Genesis: infra/besu/networkFiles/genesis.json'."\n");
fwrite(STDOUT, 'Start:   powershell -ExecutionPolicy Bypass -File scripts/besu/start-network.ps1'."\n");

function publicKeyFromPrivateKey(string $privateKey): string
{
    $ec = new EC('secp256k1');
    $key = $ec->keyFromPrivate($privateKey, 'hex');
    $publicKey = strtolower((string) $key->getPublic(false, 'hex'));

    return str_starts_with($publicKey, '04') ? substr($publicKey, 2) : $publicKey;
}

function addressFromPublicKey(string $publicKey): string
{
    return '0x'.substr(Keccak::hash(hex2bin($publicKey), 256), -40);
}

/**
 * Encode the QBFT genesis extraData RLP structure.
 *
 * @param  string|list<mixed>  $value
 */
function rlpEncode(string|array $value): string
{
    if (is_array($value)) {
        $payload = '';
        foreach ($value as $item) {
            $payload .= rlpEncode($item);
        }

        return rlpPrefix(0xc0, $payload);
    }

    if (strlen($value) === 1 && ord($value) < 0x80) {
        return $value;
    }

    return rlpPrefix(0x80, $value);
}

function rlpPrefix(int $offset, string $payload): string
{
    $length = strlen($payload);
    if ($length <= 55) {
        return chr($offset + $length).$payload;
    }

    $lengthHex = ltrim(dechex($length), '0');
    if ($lengthHex === '') {
        $lengthHex = '0';
    }
    if (strlen($lengthHex) % 2 !== 0) {
        $lengthHex = '0'.$lengthHex;
    }

    return chr($offset + 55 + (int) (strlen($lengthHex) / 2)).hex2bin($lengthHex).$payload;
}

function stripHexPrefix(string $value): string
{
    return str_starts_with(strtolower($value), '0x') ? substr($value, 2) : $value;
}

function ensureDirectory(string $directory): void
{
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException('Unable to create '.$directory);
    }
}

function writeFile(string $path, string $contents): void
{
    ensureDirectory(dirname($path));
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException('Unable to write '.$path);
    }
}
