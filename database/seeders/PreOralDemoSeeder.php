<?php

namespace Database\Seeders;

use App\Models\ActivityComplianceDoc;
use App\Models\BudgetItem;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\OrgFundSource;
use App\Models\OrgReportStatus;
use App\Models\StudentFeedback;
use App\Models\TosaApplicant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PreOralDemoSeeder extends Seeder
{
    public function run(): void
    {
        $colleges = [
            'College of Accountancy, Business, Economics, and International Hospitality Management' => 'CABEIHM',
            'College of Arts and Sciences' => 'CAS',
            'College of Criminal Justice Education' => 'CCJE',
            'College of Health Sciences' => 'CHS',
            'College of Informatics and Computing Sciences' => 'CICS',
            'College of Teacher Education' => 'CTE',
            'Laboratory School' => 'LAB',
        ];

        $activities = [
            [
                'title' => 'Innovation Fair Booth Series',
                'college' => 'College of Informatics and Computing Sciences',
                'organization_name' => 'CICS Student Council',
                'program' => 'Bachelor of Science in Information Technology',
                'status' => 'completed',
                'workflow_status' => 'oc_approved',
                'activity_scope' => 'in_campus',
                'location' => 'Gymnasium',
                'starts_at' => '2026-07-04 09:00:00',
                'approved_budget' => 15000,
                'implemented_budget' => 14800,
                'male_participants' => 85,
                'female_participants' => 92,
                'sdg_goals' => ['SDG 4', 'SDG 9'],
                'core_values' => ['Excellence', 'Innovation'],
            ],
            [
                'title' => 'Leadership Summit 2026',
                'college' => 'College of Arts and Sciences',
                'organization_name' => 'CAS Organization',
                'program' => 'Bachelor of Science in Psychology',
                'status' => 'upcoming',
                'workflow_status' => 'oso_review',
                'activity_scope' => 'local_off_campus',
                'location' => 'Taal Building',
                'starts_at' => '2026-09-20 06:00:00',
                'approved_budget' => 75000,
                'implemented_budget' => 42750,
                'male_participants' => 60,
                'female_participants' => 75,
                'sdg_goals' => ['SDG 4', 'SDG 16'],
                'core_values' => ['Leadership', 'Integrity'],
            ],
            [
                'title' => 'Campus Wellness Week',
                'college' => 'College of Health Sciences',
                'organization_name' => 'CHS Student Org',
                'program' => 'Bachelor of Science in Nursing',
                'status' => 'upcoming',
                'workflow_status' => 'sdo_review',
                'activity_scope' => 'in_campus',
                'location' => 'Gymnasium',
                'starts_at' => '2026-09-08 10:00:00',
                'approved_budget' => 42500,
                'implemented_budget' => 24900,
                'male_participants' => 110,
                'female_participants' => 140,
                'sdg_goals' => ['SDG 3', 'SDG 4'],
                'core_values' => ['Service', 'Compassion'],
            ],
            [
                'title' => 'Criminology Outreach Caravan',
                'college' => 'College of Criminal Justice Education',
                'organization_name' => 'CCJE Society',
                'program' => 'Bachelor of Science in Criminology',
                'status' => 'upcoming',
                'workflow_status' => 'college_review',
                'activity_scope' => 'in_campus',
                'location' => 'Mini Forest',
                'starts_at' => '2026-09-15 13:00:00',
                'approved_budget' => 22000,
                'implemented_budget' => 8000,
                'male_participants' => 95,
                'female_participants' => 70,
                'sdg_goals' => ['SDG 16', 'SDG 11'],
                'core_values' => ['Integrity', 'Justice'],
            ],
            [
                'title' => 'Teacher Education Literacy Drive',
                'college' => 'College of Teacher Education',
                'organization_name' => 'CTE League',
                'program' => 'Bachelor of Secondary Education Major in English',
                'status' => 'upcoming',
                'workflow_status' => 'ovcaa_review',
                'activity_scope' => 'in_campus',
                'location' => 'Library Annex',
                'starts_at' => '2026-09-30 09:00:00',
                'approved_budget' => 18000,
                'implemented_budget' => 12000,
                'male_participants' => 40,
                'female_participants' => 88,
                'sdg_goals' => ['SDG 4', 'SDG 5'],
                'core_values' => ['Excellence', 'Service'],
            ],
            [
                'title' => 'CABEIHM Entrepreneurship Expo',
                'college' => 'College of Accountancy, Business, Economics, and International Hospitality Management',
                'organization_name' => 'CABEIHM Org',
                'program' => 'Bachelor of Science in Business Administration Major in Marketing Management',
                'status' => 'upcoming',
                'workflow_status' => 'created',
                'activity_scope' => 'in_campus',
                'location' => 'Main Lobby',
                'starts_at' => '2026-10-05 08:00:00',
                'approved_budget' => 35000,
                'implemented_budget' => 5000,
                'male_participants' => 70,
                'female_participants' => 95,
                'sdg_goals' => ['SDG 8', 'SDG 9'],
                'core_values' => ['Innovation', 'Excellence'],
            ],
            [
                'title' => 'Lab School Science Fair',
                'college' => 'Laboratory School',
                'organization_name' => 'Lab School Council',
                'program' => 'Senior High',
                'status' => 'upcoming',
                'workflow_status' => 'oso_review',
                'activity_scope' => 'in_campus',
                'location' => 'Lab School Hall',
                'starts_at' => '2026-09-02 13:00:00',
                'approved_budget' => 12000,
                'implemented_budget' => 9500,
                'male_participants' => 55,
                'female_participants' => 48,
                'sdg_goals' => ['SDG 4'],
                'core_values' => ['Excellence'],
            ],
            [
                'title' => 'BatStateU Sportsfest 2026',
                'college' => 'College of Arts and Sciences',
                'organization_name' => 'SSC Sports Committee',
                'program' => 'Bachelor of Arts in Communication',
                'status' => 'upcoming',
                'workflow_status' => 'oc_approved',
                'activity_scope' => 'in_campus',
                'location' => 'Sports Complex',
                'starts_at' => '2026-10-15 07:00:00',
                'approved_budget' => 40000,
                'implemented_budget' => 20000,
                'male_participants' => 200,
                'female_participants' => 180,
                'sdg_goals' => ['SDG 3', 'SDG 4'],
                'core_values' => ['Teamwork', 'Excellence'],
            ],
        ];

        foreach ($activities as $row) {
            $activity = OrgActivity::query()->updateOrCreate(
                ['title' => $row['title']],
                array_merge($row, [
                    'description' => $row['title'].' — pre-oral demo activity for '.$row['college'].'.',
                    'ends_at' => Carbon::parse($row['starts_at'])->addHours(4),
                ])
            );

            foreach ([
                ['proposal', 'Activity Proposal'],
                ['budget', 'Budget Breakdown'],
                ['attendance', 'Attendance Sheet'],
            ] as [$key, $title]) {
                ActivityComplianceDoc::query()->updateOrCreate(
                    [
                        'org_activity_id' => $activity->id,
                        'doc_key' => $key,
                    ],
                    [
                        'title' => $title,
                        'status' => $activity->workflow_status === 'oc_approved' ? 'approved' : 'pending',
                    ]
                );
            }

            BudgetItem::query()->updateOrCreate(
                ['title' => $row['title'].' Budget'],
                [
                    'category' => $row['activity_scope'] === 'local_off_campus' ? 'Off-Campus' : 'In-Campus',
                    'college' => $row['college'],
                    'organization_name' => $row['organization_name'],
                    'allocated' => $row['approved_budget'],
                    'utilized' => $row['implemented_budget'],
                    'fiscal_year' => '2026',
                    'notes' => 'Demo seeded item',
                    'supplier' => 'BatStateU Cooperative Store',
                    'is_approved' => in_array($row['workflow_status'], ['oc_approved', 'ovcaa_review', 'sdo_review'], true),
                    'scope' => $row['activity_scope'],
                ]
            );
        }

        foreach ($colleges as $college => $abbr) {
            $account = OrgFundAccount::query()->updateOrCreate(
                [
                    'organization_name' => $abbr.' Student Organization',
                    'fiscal_year' => '2025-2026',
                ],
                [
                    'college' => $college,
                    'beginning_balance' => 25000,
                    'total_funds' => 85000,
                    'total_funds_received' => 60000,
                ]
            );

            OrgFundSource::query()->updateOrCreate(
                [
                    'org_fund_account_id' => $account->id,
                    'category' => 'ssc_fee',
                ],
                ['label' => 'SSC Fee', 'amount' => 35000]
            );
            OrgFundSource::query()->updateOrCreate(
                [
                    'org_fund_account_id' => $account->id,
                    'category' => 'fundraising',
                ],
                ['label' => 'Fundraising', 'amount' => 15000]
            );
            OrgFundSource::query()->updateOrCreate(
                [
                    'org_fund_account_id' => $account->id,
                    'category' => 'sponsorship',
                ],
                ['label' => 'Sponsorship', 'amount' => 10000]
            );
        }

        foreach (['fr', 'ar', 'budget'] as $type) {
            OrgReportStatus::query()->updateOrCreate(
                [
                    'report_type' => $type,
                    'organization_name' => 'CICS Student Council',
                    'academic_year' => '2025-2026',
                    'semester' => '1st Semester',
                ],
                [
                    'college' => 'College of Informatics and Computing Sciences',
                    'status' => $type === 'fr' ? 'oso_review' : 'draft',
                ]
            );
        }

        $applicants = [
            ['full_name' => 'Ana Reyes', 'program' => 'BSIT', 'subsection' => 'pending', 'year_level' => '3'],
            ['full_name' => 'Mark Santos', 'program' => 'BS Psychology', 'subsection' => 'screening', 'year_level' => '2'],
            ['full_name' => 'Liza Cruz', 'program' => 'BS Nursing', 'subsection' => 'interview', 'year_level' => '4'],
            ['full_name' => 'Jon Dela Cruz', 'program' => 'BS Criminology', 'subsection' => 'accepted', 'year_level' => '3'],
            ['full_name' => 'Mia Flores', 'program' => 'BSED English', 'subsection' => 'rejected', 'year_level' => '2'],
            ['full_name' => 'Carlo Mendoza', 'program' => 'BSBA MM', 'subsection' => 'pending', 'year_level' => '1'],
        ];

        foreach ($applicants as $i => $applicant) {
            TosaApplicant::query()->updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $applicant['full_name'])).'@g.batstate-u.edu.ph'],
                array_merge($applicant, [
                    'sr_code' => '23-'.str_pad((string) (70000 + $i), 5, '0', STR_PAD_LEFT),
                    'college' => array_key_first($colleges),
                    'organization_name' => 'OrgChain Demo Org',
                    'status' => 'submitted',
                    'requirements' => ['cv' => true, 'good_moral' => true, 'scholastic' => $i % 2 === 0],
                ])
            );
        }

        StudentFeedback::query()->updateOrCreate(
            ['body' => 'Please improve announcement lead time for major campus events.'],
            [
                'author_name' => 'Student Voice',
                'college' => 'College of Informatics and Computing Sciences',
                'program' => 'BSIT',
                'topic' => 'admin',
                'is_anonymous' => false,
                'visibility' => 'oso',
            ]
        );

        StudentFeedback::query()->updateOrCreate(
            ['body' => 'More wellness activities would help during midterms.'],
            [
                'author_name' => 'Anonymous',
                'college' => 'College of Health Sciences',
                'program' => 'BS Nursing',
                'topic' => 'activities',
                'is_anonymous' => true,
                'visibility' => 'oso',
            ]
        );
    }
}
