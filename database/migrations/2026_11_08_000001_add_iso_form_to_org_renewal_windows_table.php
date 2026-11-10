<?php

use App\Models\OrgRenewalWindow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isoDoc = [
            'key' => 'iso_form',
            'title' => 'Application for Recognition/Renewal of Student Organization',
            'attachment_label' => 'ISO Form',
            'template_path' => 'renewal-templates/BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03.docx',
            'template_name' => 'BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03.docx',
            'pdf_path' => 'renewal-templates/BatStateU-FO-SOA-01_Application for Recognition, Renewal of Student Organization_Rev. 03.pdf',
        ];

        $windows = DB::connection('mysql')->table('org_renewal_windows')->get();
        foreach ($windows as $window) {
            $docs = json_decode($window->required_docs, true);
            if (is_array($docs)) {
                $hasIso = collect($docs)->contains('key', 'iso_form');
                if (! $hasIso) {
                    array_unshift($docs, $isoDoc);
                    DB::connection('mysql')->table('org_renewal_windows')
                        ->where('id', $window->id)
                        ->update(['required_docs' => json_encode($docs)]);
                }
            }
        }
    }

    public function down(): void
    {
        $windows = DB::connection('mysql')->table('org_renewal_windows')->get();
        foreach ($windows as $window) {
            $docs = json_decode($window->required_docs, true);
            if (is_array($docs)) {
                $docs = array_values(array_filter($docs, fn ($d) => ($d['key'] ?? '') !== 'iso_form'));
                DB::connection('mysql')->table('org_renewal_windows')
                    ->where('id', $window->id)
                    ->update(['required_docs' => json_encode($docs)]);
            }
        }
    }
};
