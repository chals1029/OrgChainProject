<?php

namespace App\Services;

use Elliptic\EC;
use InvalidArgumentException;
use RuntimeException;
use Web3p\EthereumTx\Transaction;
use kornrunner\Keccak;

class BesuTransactionSigner
{
    private static bool $dependenciesRegistered = false;

    public function ensureDependencies(): void
    {
        if (self::$dependenciesRegistered) {
            return;
        }

        $loader = require base_path('vendor/autoload.php');
        if ($loader instanceof \Composer\Autoload\ClassLoader) {
            $loader->addPsr4('Elliptic\\', base_path('vendor/simplito/elliptic-php/lib'));
            $loader->addPsr4('BN\\', base_path('vendor/simplito/bn-php/lib'));
            $loader->addPsr4('BI\\', base_path('vendor/simplito/bigint-wrapper-php/lib'));
            $loader->addPsr4('Web3p\\EthereumTx\\', base_path('vendor/web3p/ethereum-tx/src'));
            $loader->addPsr4('Web3p\\EthereumUtil\\', base_path('vendor/web3p/ethereum-util/src'));
            $loader->addPsr4('Web3p\\RLP\\', base_path('vendor/web3p/rlp/src'));
            $loader->addPsr4('kornrunner\\', base_path('vendor/kornrunner/keccak/src'));
        }

        self::$dependenciesRegistered = true;
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array{raw_transaction: string, from: string}
     */
    public function signLegacy(array $transaction): array
    {
        $this->ensureDependencies();
        $privateKey = $this->privateKey();
        $signed = new Transaction($transaction);

        return [
            'raw_transaction' => $signed->sign($privateKey),
            'from' => $this->addressFromPrivateKey($privateKey),
        ];
    }

    public function privateKey(): string
    {
        $this->ensureDependencies();
        if (! extension_loaded('gmp')) {
            throw new RuntimeException(
                'Besu transaction signing requires the PHP GMP extension. Enable extension=gmp in the PHP runtime used by Laravel.'
            );
        }

        $configured = trim((string) config('besu.signer_private_key', ''));
        if ($configured === '') {
            $path = (string) config('besu.signer_private_key_file', '');
            if ($path !== '' && ! $this->isAbsolutePath($path)) {
                $path = base_path($path);
            }
            if ($path !== '' && is_file($path)) {
                $configured = trim((string) file_get_contents($path));
            }
        }

        $privateKey = strtolower($this->stripHexPrefix($configured));
        if (! preg_match('/^[0-9a-f]{64}$/', $privateKey)) {
            throw new InvalidArgumentException(
                'BESU_SIGNER_PRIVATE_KEY or BESU_SIGNER_PRIVATE_KEY_FILE must contain exactly 32 bytes of hexadecimal key material.'
            );
        }

        return $privateKey;
    }

    public function addressFromPrivateKey(string $privateKey): string
    {
        $this->ensureDependencies();
        $privateKey = strtolower($this->stripHexPrefix(trim($privateKey)));
        if (! preg_match('/^[0-9a-f]{64}$/', $privateKey)) {
            throw new InvalidArgumentException('The Besu signer private key is not valid hexadecimal key material.');
        }

        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey, 'hex');
        $publicKey = strtolower((string) $key->getPublic(false, 'hex'));
        if (str_starts_with($publicKey, '04')) {
            $publicKey = substr($publicKey, 2);
        }

        return '0x'.substr(Keccak::hash(hex2bin($publicKey), 256), -40);
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
