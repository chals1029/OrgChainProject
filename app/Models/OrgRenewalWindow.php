<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgRenewalWindow extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'academic_year',
        'semester',
        'is_open',
        'opens_at',
        'closes_at',
        'instructions',
        'required_docs',
        'opened_by',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'required_docs' => 'array',
        ];
    }

    /**
     * Default renewal packet checklist (OSO recognition requirements).
     *
     * @return list<array{key: string, title: string}>
     */
    public static function defaultRequiredDocs(): array
    {
        return [
            ['key' => 'commitment_letter', 'title' => 'Commitment Letter of the Adviser'],
            ['key' => 'academic_certification', 'title' => 'Certification of Academic Qualifications'],
            ['key' => 'org_profile', 'title' => 'Profile of Student Organization'],
            ['key' => 'list_of_members', 'title' => 'List of Members'],
            ['key' => 'org_history', 'title' => 'History of Student Organization'],
            ['key' => 'revolving_fund', 'title' => 'Declaration of the Organization Revolving Fund'],
            ['key' => 'constitution', 'title' => 'Ratified Constitution and By-Law'],
            ['key' => 'adviser_officers_profile', 'title' => 'Student Organization Adviser and Officers Profile'],
            ['key' => 'plan_of_activities', 'title' => 'Plan of Activities'],
            ['key' => 'specimen_signature', 'title' => 'Specimen Signature'],
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(OrgRenewalSubmission::class, 'renewal_window_id');
    }

    public function requiredDocList(): array
    {
        $docs = $this->required_docs;
        if (! is_array($docs) || $docs === []) {
            return self::defaultRequiredDocs();
        }

        return $docs;
    }

    public function isAcceptingSubmissions(): bool
    {
        if (! $this->is_open) {
            return false;
        }

        $now = now();
        if ($this->opens_at && $now->lt($this->opens_at)) {
            return false;
        }
        if ($this->closes_at && $now->gt($this->closes_at)) {
            return false;
        }

        return true;
    }
}
