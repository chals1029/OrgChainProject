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
     * @return list<array{key: string, title: string, template_path?: string, template_name?: string}>
     */
    public static function defaultRequiredDocs(): array
    {
        return [
            [
                'key' => 'commitment_letter',
                'title' => 'Commitment Letter of the Adviser',
                'attachment_label' => 'Attachment A',
                'template_path' => 'renewal-templates/Attachment A_ Commitment Letter of the Adviser.docx',
                'template_name' => 'Attachment A_ Commitment Letter of the Adviser.docx',
                'pdf_path' => 'renewal-templates/Attachment A_ Commitment Letter of the Adviser.pdf',
            ],
            [
                'key' => 'academic_certification',
                'title' => 'Certification of Academic Qualifications',
                'attachment_label' => 'Attachment B',
                'template_path' => 'renewal-templates/Attachment B_ Certificate for Academic Qualifications.docx',
                'template_name' => 'Attachment B_ Certificate for Academic Qualifications.docx',
                'pdf_path' => 'renewal-templates/Attachment B_ Certificate for Academic Qualifications.pdf',
            ],
            [
                'key' => 'org_profile',
                'title' => 'Profile of Student Organization',
                'attachment_label' => 'Attachment C',
                'template_path' => 'renewal-templates/Attachment C_ Profile of Student Organization _.docx',
                'template_name' => 'Attachment C_ Profile of Student Organization _.docx',
                'pdf_path' => 'renewal-templates/Attachment C_ Profile of Student Organization _.pdf',
            ],
            [
                'key' => 'list_of_members',
                'title' => 'List of Members',
                'attachment_label' => 'Attachment D',
                'template_path' => 'renewal-templates/Attachment D_ List of Members.docx',
                'template_name' => 'Attachment D_ List of Members.docx',
                'pdf_path' => 'renewal-templates/Attachment D_ List of Members.pdf',
            ],
            [
                'key' => 'org_history',
                'title' => 'History of Student Organization',
                'attachment_label' => 'Attachment E',
                'template_path' => 'renewal-templates/Attachment E_ History of Student Organization.docx',
                'template_name' => 'Attachment E_ History of Student Organization.docx',
                'pdf_path' => 'renewal-templates/Attachment E_ History of Student Organization.pdf',
            ],
            [
                'key' => 'revolving_fund',
                'title' => 'Declaration of the Organization Revolving Fund',
                'attachment_label' => 'Attachment F',
                'template_path' => 'renewal-templates/Attachment F. Declaration of Organization_s Revolving Fund.docx',
                'template_name' => 'Attachment F. Declaration of Organization_s Revolving Fund.docx',
                'pdf_path' => 'renewal-templates/Attachment F. Declaration of Organization_s Revolving Fund.pdf',
            ],
            [
                'key' => 'constitution',
                'title' => 'Ratified Constitution and By-Laws',
                'attachment_label' => 'Attachment G',
                'template_path' => 'renewal-templates/Attachment G_ Organization_s Constitution and By-Laws.docx',
                'template_name' => 'Attachment G_ Organization_s Constitution and By-Laws.docx',
                'pdf_path' => 'renewal-templates/Attachment G_ Organization_s Constitution and By-Laws.pdf',
            ],
            [
                'key' => 'adviser_officers_profile',
                'title' => 'Student Organization Adviser and Officers Profile',
                'attachment_label' => 'Attachment H',
                'template_path' => 'renewal-templates/Attachment H_ Student Organization  Adviser and Officers Profile.docx',
                'template_name' => 'Attachment H_ Student Organization  Adviser and Officers Profile.docx',
                'pdf_path' => 'renewal-templates/Attachment H_ Student Organization  Adviser and Officers Profile.pdf',
            ],
            [
                'key' => 'plan_of_activities',
                'title' => 'Plan of Activities',
                'attachment_label' => 'Attachment I',
                'template_path' => 'renewal-templates/Attachment I_ Plan of Activities.docx',
                'template_name' => 'Attachment I_ Plan of Activities.docx',
                'pdf_path' => 'renewal-templates/Attachment I_ Plan of Activities.pdf',
            ],
            [
                'key' => 'specimen_signature',
                'title' => 'Specimen Signatures',
                'attachment_label' => 'Attachment J',
                'template_path' => 'renewal-templates/Attachment J_ Specimen Signatures.docx',
                'template_name' => 'Attachment J_ Specimen Signatures.docx',
                'pdf_path' => 'renewal-templates/Attachment J_ Specimen Signatures.pdf',
            ],
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
