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
                'auto_lock_interval' => 15,
                'tosa_gate' => true,
                'tosa_evaluation_mode' => 'strict',
                'master_pin_hash' => null,
                'tosa_pin_hash' => null,
            ],
            'notifications' => [
                'new_proposal_alert' => true,
                'tosa_applicant_alert' => true,
                'sound_effects' => false,
                'approval_dispatches' => true,
                'revision_alerts' => true,
                'broadcast_banner' => true,
                'email_digest_frequency' => 'daily',
                'digest_email' => 'oso.directorate@g.batstate-u.edu.ph',
            ],
            'preferences' => [
                'timezone' => 'Asia/Manila',
                'date_format' => 'MMM D, YYYY',
                'time_format' => '12h',
                'language' => 'en',
                'theme' => 'red-spartan',
                'high_contrast' => false,
                'micro_animations' => true,
                'default_landing_module' => 'dashboard',
                'table_page_size' => 7,
            ],
            'records' => [
                'auto_archive' => true,
                'retention_schedule' => '5',
                'archive_storage_location' => 'BSU-VAULT-AY2627-NODE01',
                'cloud_backup' => true,
            ],
        ];
    }

    public static function valuesFor(string $scope): array
    {
        $record = static::query()->where('scope', $scope)->first();

        return array_replace_recursive(static::defaults(), $record?->values ?? []);
    }

    public static function forScope(string $scope): self
    {
        $record = static::query()->firstOrCreate(
            ['scope' => $scope],
            ['values' => static::defaults()]
        );

        $merged = array_replace_recursive(static::defaults(), $record->values ?? []);
        if ($record->values !== $merged) {
            $record->forceFill(['values' => $merged])->save();
        }

        return $record;
    }

    public static function publicValuesFor(string $scope): array
    {
        $values = static::valuesFor($scope);
        $values['security']['master_pin_configured'] = filled($values['security']['master_pin_hash'] ?? null);
        $values['security']['tosa_pin_configured'] = filled($values['security']['tosa_pin_hash'] ?? null);
        unset(
            $values['security']['two_factor'],
            $values['security']['ip_whitelist'],
            $values['security']['biometric']
        );
        unset($values['security']['master_pin_hash'], $values['security']['tosa_pin_hash']);

        return $values;
    }
}
