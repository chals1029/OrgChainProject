<?php

namespace App\Services;

use Carbon\Carbon;
use Throwable;

final class OrgTimeService
{
    public static function timezone(): string
    {
        return (string) config('app.timezone', 'Asia/Manila');
    }

    public static function zoneLabel(): string
    {
        return match (self::timezone()) {
            'Asia/Manila' => 'PHT (UTC+8)',
            'UTC' => 'UTC',
            default => self::timezone(),
        };
    }

    public static function format(mixed $value, string $format = 'M j, Y g:i A'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $date = $value instanceof Carbon
                ? $value->copy()
                : Carbon::parse($value);

            return $date->setTimezone(self::timezone())->format($format).' '.self::zoneLabel();
        } catch (Throwable) {
            return '';
        }
    }
}
