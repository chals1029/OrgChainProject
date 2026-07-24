<?php

namespace App\VotingSystem\Core;

class RateLimiter
{
    public static function tooManyAttempts(string $action, string $identifier, int $maxAttempts, int $decaySeconds): bool
    {
        $key = self::key($action, $identifier);
        $data = self::read();
        $now = time();
        $attempts = array_values(array_filter(
            $data[$key]['attempts'] ?? [],
            fn (int $timestamp): bool => $timestamp > $now - $decaySeconds
        ));

        $data[$key]['attempts'] = $attempts;
        self::write($data);

        return count($attempts) >= $maxAttempts;
    }

    public static function hit(string $action, string $identifier, int $decaySeconds): void
    {
        self::hitAndCount($action, $identifier, $decaySeconds);
    }

    public static function hitAndCount(string $action, string $identifier, int $decaySeconds): int
    {
        $key = self::key($action, $identifier);
        $data = self::read();
        $now = time();
        $attempts = array_values(array_filter(
            $data[$key]['attempts'] ?? [],
            fn (int $timestamp): bool => $timestamp > $now - $decaySeconds
        ));

        $attempts[] = $now;
        $data[$key]['attempts'] = $attempts;
        self::write($data);

        return count($attempts);
    }

    public static function count(string $action, string $identifier, int $decaySeconds): int
    {
        $key = self::key($action, $identifier);
        $data = self::read();
        $now = time();
        $attempts = array_values(array_filter(
            $data[$key]['attempts'] ?? [],
            fn (int $timestamp): bool => $timestamp > $now - $decaySeconds
        ));

        $data[$key]['attempts'] = $attempts;
        self::write($data);

        return count($attempts);
    }

    public static function clear(string $action, string $identifier): void
    {
        $key = self::key($action, $identifier);
        $data = self::read();
        unset($data[$key]);
        self::write($data);
    }

    private static function key(string $action, string $identifier): string
    {
        return hash('sha256', $action . '|' . strtolower(trim($identifier)));
    }

    private static function path(): string
    {
        return storage_path('app/voting/rate-limits.json');
    }

    private static function read(): array
    {
        $path = self::path();

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        $data = json_decode($json ?: '{}', true);

        return is_array($data) ? $data : [];
    }

    private static function write(array $data): void
    {
        $path = self::path();

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, json_encode($data), LOCK_EX);
    }
}
