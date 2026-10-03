<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BesuRpcClient
{
    private int $nextId = 1;

    /**
     * Execute a JSON-RPC request against a Besu HTTP endpoint.
     *
     * @param  list<mixed>  $params
     */
    public function call(string $method, array $params = []): mixed
    {
        $response = Http::timeout((int) config('besu.rpc_timeout_seconds', 5))
            ->acceptJson()
            ->post((string) config('besu.rpc_url'), [
                'jsonrpc' => '2.0',
                'id' => $this->nextId++,
                'method' => $method,
                'params' => $params,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Besu RPC returned HTTP '.$response->status().' for '.$method.'.');
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('Besu RPC returned an invalid JSON response for '.$method.'.');
        }

        if (isset($body['error']) && is_array($body['error'])) {
            $message = (string) ($body['error']['message'] ?? 'Unknown Besu RPC error.');
            $code = isset($body['error']['code']) ? ' ('.$body['error']['code'].')' : '';

            throw new RuntimeException('Besu RPC error'.$code.': '.$message);
        }

        return $body['result'] ?? null;
    }

    public function isAvailable(): bool
    {
        try {
            $this->call('web3_clientVersion');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function waitForReceipt(string $transactionHash): array
    {
        $deadline = microtime(true) + (int) config('besu.receipt_timeout_seconds', 45);

        do {
            $receipt = $this->call('eth_getTransactionReceipt', [$transactionHash]);
            if (is_array($receipt)) {
                return $receipt;
            }

            usleep((int) config('besu.poll_interval_ms', 250) * 1000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Timed out waiting for Besu transaction '.$transactionHash.'.');
    }

    public static function quantityToInt(mixed $quantity): int
    {
        $value = trim((string) $quantity);
        if ($value === '') {
            return 0;
        }

        if (str_starts_with(strtolower($value), '0x')) {
            return (int) hexdec(substr($value, 2));
        }

        return (int) $value;
    }
}

