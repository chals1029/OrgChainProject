<?php

namespace App\Services\Health;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

class SystemHealthSnapshot
{
    public const SERVICES = ['database', 'blockchain', 'private-storage', 'document-processing', 'institutional-sign-in', 'mail-provider'];

    public const STATES = ['operational', 'degraded', 'unavailable', 'unknown'];

    /** @return array<string, array{status: string, checkedAt: ?string, startedAt: ?string}> */
    public function read(): array
    {
        $result = array_fill_keys(self::SERVICES, ['status' => 'unknown', 'checkedAt' => null, 'startedAt' => null]);
        $path = (string) config('system-health.snapshot_path');
        try {
            if (is_link($path) || is_link(dirname($path)) || ! is_file($path) || ! is_readable($path) || filesize($path) > 16384) {
                return $result;
            }
            $body = json_decode(file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($body) || ! is_array($body['services'] ?? null)) {
                return $result;
            }
            foreach (self::SERVICES as $id) {
                $entry = $body['services'][$id] ?? null;
                if (! is_array($entry) || ! in_array($entry['status'] ?? null, self::STATES, true)) {
                    continue;
                }
                $checked = $this->timestamp($entry['checkedAt'] ?? null);
                $started = $this->timestamp($entry['startedAt'] ?? null);
                if ($checked === null || $started === null || $started > $checked || $checked > time()) {
                    continue;
                }
                $result[$id] = [
                    'status' => time() - $checked > (int) config('system-health.max_age_seconds', 900) ? 'unknown' : $entry['status'],
                    'checkedAt' => gmdate('Y-m-d\TH:i:s\Z', $checked),
                    'startedAt' => gmdate('Y-m-d\TH:i:s\Z', $started),
                ];
            }
        } catch (Throwable) {
            // Missing/corrupt observations are not evidence that a service is up.
        }

        return $result;
    }

    public function prepareDirectory(): string
    {
        $directory = dirname((string) config('system-health.snapshot_path'));
        if (is_link($directory) || (file_exists($directory) && ! is_dir($directory))) {
            throw new RuntimeException('Private health workspace is unavailable.');
        }
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Private health workspace is unavailable.');
        }
        if (! is_writable($directory)) {
            throw new RuntimeException('Private health workspace is unavailable.');
        }

        return $directory;
    }

    /** @param array<string, array{status: string, checkedAt: string, startedAt: ?string}> $services */
    public function publish(array $services): void
    {
        $directory = $this->prepareDirectory();
        $safe = [];
        foreach (self::SERVICES as $id) {
            $entry = $services[$id] ?? [];
            if (! in_array($entry['status'] ?? null, self::STATES, true)) {
                throw new RuntimeException('Invalid health observation.');
            }
            $safe[$id] = [
                'status' => $entry['status'],
                'checkedAt' => $entry['checkedAt'],
                'startedAt' => $entry['startedAt'],
            ];
        }
        $temporary = tempnam($directory, '.health-');
        if ($temporary === false) {
            throw new RuntimeException('Health observations could not be stored.');
        }
        try {
            if (PHP_OS_FAMILY !== 'Windows' && ! chmod($temporary, 0600)) {
                throw new RuntimeException('Health observations could not be secured.');
            }
            $json = json_encode(['services' => $safe], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            if (file_put_contents($temporary, $json, LOCK_EX) !== strlen($json) || ! rename($temporary, (string) config('system-health.snapshot_path'))) {
                throw new RuntimeException('Health observations could not be stored.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    private function timestamp(mixed $value): ?int
    {
        if (! is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value ? $date->getTimestamp() : null;
    }
}
