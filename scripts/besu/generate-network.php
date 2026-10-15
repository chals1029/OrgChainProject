<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Mdanter\Ecc\EccFactory;
use kornrunner\Keccak;

if (! extension_loaded('gmp')) {
    fwrite(STDERR, "PHP GMP is required. On Windows, try: php -d extension=php_gmp.dll scripts/besu/generate-network.php\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
$output = $root.DIRECTORY_SEPARATOR.'infra'.DIRECTORY_SEPARATOR.'besu'.DIRECTORY_SEPARATOR.'networkFiles';
$options = getopt('', ['chain-id:']);
$chainId = filter_var($options['chain-id'] ?? 20260920, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($chainId === false) {
    throw new InvalidArgumentException('--chain-id must be a positive integer.');
}
if (file_exists($root.'/infra/besu/.env') || is_link($output)
    || (is_dir($output) && array_diff(scandir($output), ['.', '..', '.gitkeep']) !== [])
    || (file_exists($output) && ! is_dir($output))) {
    throw new RuntimeException('An existing network or Compose environment was found. Archive it before generating another network.');
}
umask(0077);
$order = gmp_init('fffffffffffffffffffffffffffffffebaaedce6af48a03bbfd25e8cd0364141', 16);
$privateKeys = [];
foreach (['node-1', 'node-2', 'node-3', 'node-4', 'app'] as $name) {
    do {
        $privateKey = bin2hex(random_bytes(32));
        $number = gmp_init($privateKey, 16);
    } while (gmp_cmp($number, 0) === 0 || gmp_cmp($number, $order) >= 0 || in_array($privateKey, $privateKeys, true));
    $privateKeys[$name] = $privateKey;
}

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

fwrite(STDOUT, "Generated a four-validator QBFT network with random private keys.\n");
fwrite(STDOUT, 'App signer address: '.$accounts['app']['address']."\n");
fwrite(STDOUT, 'Genesis: infra/besu/networkFiles/genesis.json'."\n");
fwrite(STDOUT, "Start the configured Docker Compose network, then deploy the anchor contract.\n");

function publicKeyFromPrivateKey(string $privateKey): string
{
    static $generator;
    $generator ??= EccFactory::getSecgCurves()->generator256k1();
    $point = $generator->getPrivateKeyFrom(gmp_init($privateKey, 16))->getPublicKey()->getPoint();

    return str_pad(gmp_strval($point->getX(), 16), 64, '0', STR_PAD_LEFT)
        .str_pad(gmp_strval($point->getY(), 16), 64, '0', STR_PAD_LEFT);
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
    if (is_link($directory) || (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory))) {
        throw new RuntimeException('Unable to create '.$directory);
    }
}

function writeFile(string $path, string $contents): void
{
    ensureDirectory(dirname($path));
    $handle = fopen($path, 'xb');
    if ($handle === false) {
        throw new RuntimeException('Unable to create a new file at '.$path);
    }
    try {
        if (fwrite($handle, $contents) !== strlen($contents)) {
            throw new RuntimeException('Unable to write '.$path);
        }
    } finally {
        fclose($handle);
    }
}
