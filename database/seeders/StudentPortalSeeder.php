<?php

namespace Database\Seeders;

use App\Models\BudgetItem;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\OrgActivity;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentPortalSeeder extends Seeder
{
    public function run(): void
    {
        $demo = Student::query()->updateOrCreate(
            ['sr_code' => '21-00001'],
            [
                'name' => 'Charles Samotanez',
                'email' => '21-00001@g.batstate-u.edu.ph',
                'password' => 'Student@2026!',
                'college' => 'College of Informatics and Computing Sciences',
                'program' => 'BS Information Technology',
                'year_level' => '4th Year',
                'is_active' => true,
            ]
        );

        Student::query()->updateOrCreate(
            ['sr_code' => '21-00002'],
            [
                'name' => 'Maria Santos',
                'email' => '21-00002@g.batstate-u.edu.ph',
                'password' => 'Student@2026!',
                'college' => 'College of Arts and Sciences',
                'program' => 'BS Psychology',
                'year_level' => '3rd Year',
                'is_active' => true,
            ]
        );

        if (BudgetItem::query()->count() === 0) {
            BudgetItem::insert([
                [
                    'title' => 'Leadership Training Summit',
                    'category' => 'Programs',
                    'allocated' => 85000,
                    'utilized' => 62000,
                    'fiscal_year' => '2026',
                    'notes' => 'Venue, speakers, and kits for officer development.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Community Outreach Drive',
                    'category' => 'Extension',
                    'allocated' => 45000,
                    'utilized' => 31800,
                    'fiscal_year' => '2026',
                    'notes' => 'School supplies and logistics for partner barangay.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'OrgChain Platform Operations',
                    'category' => 'Operations',
                    'allocated' => 30000,
                    'utilized' => 12450,
                    'fiscal_year' => '2026',
                    'notes' => 'Hosting, domain, and documentation printing.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'Sports Fest Contingency',
                    'category' => 'Sports',
                    'allocated' => 25000,
                    'utilized' => 8900,
                    'fiscal_year' => '2026',
                    'notes' => 'Uniforms and first-aid kit reserve.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $activities = [
            [
                'title' => 'General Assembly 2026',
                'description' => 'Open forum on org priorities, budget briefing, and officer updates.',
                'status' => 'upcoming',
                'location' => 'Main AVR',
                'starts_at' => now()->addDays(12)->setTime(13, 0),
                'ends_at' => now()->addDays(12)->setTime(16, 0),
            ],
            [
                'title' => 'Blood Donation Drive',
                'description' => 'Partnered medical mission with the campus clinic.',
                'status' => 'upcoming',
                'location' => 'Covered Court',
                'starts_at' => now()->addDays(20)->setTime(8, 0),
                'ends_at' => now()->addDays(20)->setTime(15, 0),
            ],
            [
                'title' => 'Tree Planting Day',
                'description' => 'Campus greening activity with college orgs.',
                'status' => 'ongoing',
                'location' => 'Eco Park',
                'starts_at' => now()->subDay()->setTime(7, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ],
            [
                'title' => 'Freshmen Orientation Booth',
                'description' => 'OrgChain booth during freshman week — QR demos and member signup.',
                'status' => 'completed',
                'location' => 'Student Plaza',
                'starts_at' => now()->subDays(18)->setTime(9, 0),
                'ends_at' => now()->subDays(18)->setTime(17, 0),
            ],
            [
                'title' => 'Transparency Town Hall',
                'description' => 'Live walkthrough of budget utilization and project milestones.',
                'status' => 'completed',
                'location' => 'Online / Zoom',
                'starts_at' => now()->subDays(7)->setTime(18, 0),
                'ends_at' => now()->subDays(7)->setTime(19, 30),
            ],
        ];

        foreach ($activities as $activity) {
            OrgActivity::query()->updateOrCreate(
                ['title' => $activity['title']],
                $activity
            );
        }

        if (CommunityPost::query()->count() === 0) {
            $townHall = OrgActivity::query()->where('title', 'Transparency Town Hall')->first();
            $orientation = OrgActivity::query()->where('title', 'Freshmen Orientation Booth')->first();
            $maria = Student::query()->where('sr_code', '21-00002')->first();

            $post1 = CommunityPost::create([
                'student_id' => $demo->id,
                'activity_id' => $townHall?->id,
                'body' => 'The Transparency Town Hall made the budget numbers actually make sense. Glad we can track utilization this clearly now.',
                'likes_count' => 1,
                'comments_count' => 1,
            ]);

            CommunityComment::create([
                'post_id' => $post1->id,
                'student_id' => $maria->id,
                'body' => 'Same! The charts helped a lot.',
            ]);

            CommunityPost::create([
                'student_id' => $maria->id,
                'activity_id' => $orientation?->id,
                'body' => 'Helped at the Freshmen Orientation booth today — lots of curious first years asking about OrgChain. Great energy!',
                'likes_count' => 0,
                'comments_count' => 0,
            ]);
        }
    }
}
