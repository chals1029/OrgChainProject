<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeSetting extends Model
{
    protected $fillable = [
        'scope',
        'values',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'general' => [
                'system_name' => 'OrgChain Student Organizations Portal',
                'office_name' => 'Office of Student Organizations (OSO)',
                'university_name' => 'Batangas State University - The National Engineering University',
                'campus_unit' => 'Gov. Pablo Borbon Main Campus I · Central Directorate',
                'contact_email' => 'oso.main@g.batstate-u.edu.ph',
                'contact_phone' => '(043) 980-0385 loc. 1144',
                'contact_location' => '3rd Floor, Student Services Center, Gov. Pablo Borbon Main Campus I, Batangas City',
                'logo_path' => null,
            ],
            'security' => [
                'session_timeout' => 15,
                'tosa_gate' => true,
                'tosa_pin_hash' => null,
            ],
        ];
    }

    public static function valuesFor(string $scope): array
    {
        $record = static::query()->where('scope', $scope)->first();

        return static::mergeKnownValues($record?->values ?? []);
    }

    public static function forScope(string $scope): self
    {
        $record = static::query()->firstOrCreate(
            ['scope' => $scope],
            ['values' => static::defaults()]
        );

        $merged = static::mergeKnownValues($record->values ?? []);
        if ($record->values !== $merged) {
            $record->forceFill(['values' => $merged])->save();
        }

        return $record;
    }

    private static function mergeKnownValues(array $values): array
    {
        $defaults = static::defaults();
        foreach ($defaults as $section => $fields) {
            $defaults[$section] = array_replace(
                $fields,
                array_intersect_key($values[$section] ?? [], $fields)
            );
        }

        return $defaults;
    }

    public static function publicValuesFor(string $scope): array
    {
        $values = static::valuesFor($scope);
        $values['security']['tosa_pin_configured'] = filled($values['security']['tosa_pin_hash'] ?? null);
        unset($values['security']['tosa_pin_hash']);

        return $values;
    }
}
