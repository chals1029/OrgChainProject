<?php

use App\Models\CommunityPost;
use App\Models\CommunityComment;
use App\Models\CommunityLike;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "1. Cleaning up community storage files...\n";
$disk = Storage::disk('public');
if ($disk->exists('community')) {
    $files = $disk->allFiles('community');
    foreach ($files as $f) {
        $disk->delete($f);
    }
}

echo "2. Truncating community tables...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('community_comment_likes')->truncate();
DB::table('community_likes')->truncate();
DB::table('community_comments')->truncate();
DB::table('community_posts')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "3. Seeding approved-activity community announcements...\n";
$posts = [
    [
        'student_id' => 5, // Michelle Vivas
        'activity_id' => 2988, // CABEIHM Day
        'body' => "Happy CABEIHM Day to all fellow Red Spartans! 🔴⚪ The celebration is officially ongoing at the University Gymnasium and CABEIHM Grounds. Don't forget to visit your organization booths, support your department showcases, and participate in today's activities!",
        'feeling' => '🎉 Celebrating',
        'tagged_users' => 'CABEIHM Student Council (CABEIHM-SC)',
        'views_count' => 48,
        'likes_count' => 2,
        'comments_count' => 2,
        'hours_ago' => 2,
        'comments' => [
            ['student_id' => 3, 'body' => 'Happy CABEIHM Day to all business, accountancy, and hospitality majors! 🔥', 'hours_ago' => 1.8],
            ['student_id' => 4, 'body' => 'Enjoy the festivities everyone! Drop by the student org booths! 👏', 'hours_ago' => 1.5],
        ],
        'likes' => [3, 4],
    ],
    [
        'student_id' => 3, // Charles Samontañez
        'activity_id' => 2985, // University Leadership Summit
        'body' => "Official Notice from the Supreme Student Council (SSC): The University Leadership Summit: All-Campus Student Assembly will be held on November 28, 2026 at the Alfonso Joson Gymnasium. All student organization officers, council executives, and class representatives are encouraged to participate as we align on transparent governance and student initiatives. See you there, Spartans!",
        'feeling' => '💡 Inspired',
        'tagged_users' => 'Supreme Student Council (SSC)',
        'views_count' => 62,
        'likes_count' => 2,
        'comments_count' => 2,
        'hours_ago' => 5,
        'comments' => [
            ['student_id' => 5, 'body' => 'Looking forward to this assembly! A vital step for student representation across the campus.', 'hours_ago' => 4.5],
            ['student_id' => 4, 'body' => "Will be attending with our council officers. Let's unite for student leadership!", 'hours_ago' => 4.2],
        ],
        'likes' => [4, 5],
    ],
    [
        'student_id' => 4, // Lilian Cabadin
        'activity_id' => 2989, // Pagsalubong: CABEIHM Freshmen Welcome
        'body' => "To all our first-year students: A warm welcome to BatStateU ARASOF-Nasugbu! Join the CABEIHM Student Council this November 15 at the Joson Gymnasium for Pagsalubong 2026. Get to know your college deans, faculty advisers, academic programs, and recognized student organizations. Welcome to the Red Spartan family!",
        'feeling' => '✨ Excited',
        'tagged_users' => 'CABEIHM Student Council (CABEIHM-SC)',
        'views_count' => 55,
        'likes_count' => 2,
        'comments_count' => 1,
        'hours_ago' => 10,
        'comments' => [
            ['student_id' => 5, 'body' => 'Welcome freshmen! Feel free to ask your student council officers if you need help finding your classrooms or requirements.', 'hours_ago' => 9.5],
        ],
        'likes' => [3, 5],
    ],
    [
        'student_id' => 3, // Charles Samontañez
        'activity_id' => 2952, // CICS Nexus
        'body' => "CICS Nexus is actively ongoing at the Gymnasium! To all Computer Science and Information Technology majors, take this opportunity to showcase your innovative capstone demos, tech exhibits, and programming workshops. Let's demonstrate the problem-solving and innovation of computing Spartans!",
        'feeling' => '🔥 Fired up',
        'tagged_users' => 'College of Informatics and Computing Sciences Student Council (CICS-SC)',
        'views_count' => 42,
        'likes_count' => 2,
        'comments_count' => 1,
        'hours_ago' => 18,
        'comments' => [
            ['student_id' => 4, 'body' => 'Impressive projects on display! Congratulations to all student developers and presenters.', 'hours_ago' => 17.2],
        ],
        'likes' => [4, 5],
    ],
    [
        'student_id' => 5, // Michelle Vivas
        'activity_id' => 41, // CICS SPORTFEST
        'body' => "Salute to all the computing athletes, coaches, and student supporters who gave their best in the CICS Sportfest at the Gymnasium! Your sportsmanship, teamwork, and Spartan spirit made this tournament truly memorable. Congratulations to all the winning teams!",
        'feeling' => '🏆 Proud',
        'tagged_users' => 'College of Informatics and Computing Sciences Student Council (CICS-SC)',
        'views_count' => 70,
        'likes_count' => 2,
        'comments_count' => 1,
        'hours_ago' => 26,
        'comments' => [
            ['student_id' => 3, 'body' => 'Incredible energy from all year levels! Great job to the organizers! 👏', 'hours_ago' => 25.5],
        ],
        'likes' => [3, 4],
    ],
];

foreach ($posts as $data) {
    $comments = $data['comments'];
    $likes = $data['likes'];
    $hoursAgo = $data['hours_ago'];
    unset($data['comments'], $data['likes'], $data['hours_ago']);

    $data['created_at'] = now()->subHours($hoursAgo);
    $data['updated_at'] = now()->subHours($hoursAgo);

    $post = CommunityPost::create($data);

    foreach ($comments as $c) {
        CommunityComment::create([
            'post_id' => $post->id,
            'student_id' => $c['student_id'],
            'body' => $c['body'],
            'created_at' => now()->subHours($c['hours_ago']),
            'updated_at' => now()->subHours($c['hours_ago']),
        ]);
    }

    foreach ($likes as $likerId) {
        CommunityLike::create([
            'post_id' => $post->id,
            'student_id' => $likerId,
            'reaction' => 'like',
            'created_at' => $post->created_at,
            'updated_at' => $post->created_at,
        ]);
    }
}

echo "Done! Seeded " . count($posts) . " realistic approved-activity community announcements.\n";
