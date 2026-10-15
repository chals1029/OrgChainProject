<?php

namespace App\Services;

use Mdanter\Ecc\EccFactory;
use InvalidArgumentException;
use RuntimeException;
use kornrunner\Ethereum\Transaction;
use kornrunner\Keccak;

class BesuTransactionSigner
{

    /**
     * @param  array<string, mixed>  $transaction
     * @return array{raw_transaction: string, from: string}
     */
    public function signLegacy(array $transaction): array
    {
        $privateKey = $this->privateKey();
        $signed = new Transaction(
            (string) ($transaction['nonce'] ?? '0x0'),
            (string) ($transaction['gasPrice'] ?? '0x0'),
            (string) ($transaction['gas'] ?? '0x0'),
            (string) ($transaction['to'] ?? ''),
            (string) ($transaction['value'] ?? '0x0'),
            (string) ($transaction['data'] ?? ''),
        );

        return [
            'raw_transaction' => '0x'.$signed->getRaw($privateKey, (int) ($transaction['chainId'] ?? 0)),
            'from' => $this->addressFromPrivateKey($privateKey),
        ];
    }

    public function privateKey(): string
    {
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
        $privateKey = strtolower($this->stripHexPrefix(trim($privateKey)));
        if (! preg_match('/^[0-9a-f]{64}$/', $privateKey)) {
            throw new InvalidArgumentException('The Besu signer private key is not valid hexadecimal key material.');
        }

        $point = EccFactory::getSecgCurves()->generator256k1()
            ->getPrivateKeyFrom(gmp_init($privateKey, 16))->getPublicKey()->getPoint();
        $publicKey = str_pad(gmp_strval($point->getX(), 16), 64, '0', STR_PAD_LEFT)
            .str_pad(gmp_strval($point->getY(), 16), 64, '0', STR_PAD_LEFT);

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
