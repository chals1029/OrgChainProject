<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\UserAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class CommunityReportIssuesTest extends TestCase
{
    use UsesLaragonDatabase;

    private UserAccount $author;
    private UserAccount $member;
    private UserAccount $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        if (! getenv('ORGCHAIN_TEST_DATABASE') || ! getenv('ORGCHAIN_TEST_VOTING_DATABASE')) {
            throw new \RuntimeException('CommunityReportIssuesTest requires isolated core and voting test databases.');
        }
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Carbon::setTestNow('2030-03-15 12:34:56');
        DB::connection('mysql')->table('student_organizations')->insert([
            ['id' => 910001, 'name' => 'Synthetic Community Alpha', 'college' => 'CICS', 'academic_year' => '2026-2027', 'is_active' => true],
            ['id' => 910002, 'name' => 'Synthetic Community Beta', 'college' => 'CICS', 'academic_year' => '2026-2027', 'is_active' => true],
        ]);
        $this->author = $this->ensureActiveStudent('99-41001');
        $this->member = $this->ensureActiveStudent('99-41002');
        $this->outsider = $this->ensureActiveStudent('99-41003');
        $this->author->forceFill(['org_id' => 910001, 'full_name' => 'Synthetic Searchable Author'])->save();
        $this->member->forceFill(['org_id' => 910001])->save();
        $this->outsider->forceFill(['org_id' => 910002])->save();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function postRecord(string $body, string $audience = 'public'): CommunityPost
    {
        return CommunityPost::create([
            'student_id' => $this->author->id, 'body' => $body, 'audience' => $audience,
            'likes_count' => 0, 'comments_count' => 0,
        ]);
    }

    public function test_organization_audience_is_preserved_and_excludes_nonmembers(): void
    {
        $this->actingAs($this->author, 'student')->post(route('portal.community.posts.store'), [
            'body' => 'Synthetic private community-regression-needle', 'audience' => 'org',
        ])->assertRedirect(route('portal.community'));
        $post = CommunityPost::query()->where('student_id', $this->author->id)->latest('id')->firstOrFail();
        $this->assertSame('org', $post->audience);
        $this->assertSame(0, (int) $post->likes_count);
        $this->assertSame(0, $post->comments()->count());
        $this->actingAs($this->member, 'student')->getJson(route('portal.community.posts.likers', $post))->assertOk();
        $this->actingAs($this->outsider, 'student')->getJson(route('portal.community.posts.likers', $post))->assertNotFound();
        $this->actingAs($this->outsider, 'student')->postJson(route('portal.community.posts.comment', $post), ['body' => 'Not allowed'])->assertNotFound();
    }

    public function test_an_unaffiliated_author_cannot_silently_publish_an_organization_post(): void
    {
        $this->author->forceFill(['org_id' => null])->save();
        $this->actingAs($this->author, 'student')->postJson(route('portal.community.posts.store'), [
            'body' => 'Synthetic restricted post', 'audience' => 'org',
        ])->assertUnprocessable()->assertJsonValidationErrors('audience');
        $this->assertFalse(CommunityPost::query()->where('student_id', $this->author->id)->where('body', 'Synthetic restricted post')->exists());
    }

    public function test_search_is_grouped_under_visibility_and_keeps_organization_posts_private(): void
    {
        $visible = $this->postRecord('Public scoped-search-needle');
        $hidden = $this->postRecord('Private scoped-search-needle', 'org');
        $response = $this->actingAs($this->outsider, 'student')->get(route('portal.community', ['community_q' => 'scoped-search-needle']));
        $response->assertOk();
        $ids = $response->viewData('posts')->pluck('id')->all();
        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertSame('scoped-search-needle', $response->viewData('communitySearch'));
    }

    public function test_search_matches_authors_and_applies_before_pagination(): void
    {
        $matched = $this->postRecord('Body without the author search term');
        $matched->forceFill(['created_at' => now()->subHour()])->save();
        for ($index = 0; $index < 12; $index++) {
            $this->postRecord('Unrelated newer post '.$index)->forceFill(['student_id' => $this->outsider->id])->save();
        }
        $response = $this->actingAs($this->outsider, 'student')->get(route('portal.community', ['community_q' => 'Searchable Author']));
        $response->assertOk();
        $this->assertContains($matched->id, $response->viewData('posts')->pluck('id')->all());
        $this->assertSame(1, $response->viewData('posts')->total());
    }

    public function test_reaction_changes_replace_the_existing_reaction_instead_of_adding_fake_counts(): void
    {
        $post = $this->postRecord('Synthetic reaction transition');
        $this->actingAs($this->member, 'student')->postJson(route('portal.community.posts.like', $post), ['reaction' => 'love'])
            ->assertOk()->assertJsonPath('reaction', 'love')->assertJsonPath('likes_count', 1);
        $this->postJson(route('portal.community.posts.like', $post), ['reaction' => 'sad'])
            ->assertOk()->assertJsonPath('reaction', 'sad')->assertJsonPath('likes_count', 1);
        $this->assertSame('sad', $post->likes()->sole()->reaction);
        $this->postJson(route('portal.community.posts.like', $post), ['reaction' => 'sad'])
            ->assertOk()->assertJsonPath('liked', false)->assertJsonPath('likes_count', 0);
        $this->assertSame(0, $post->likes()->count());
    }

    public function test_organization_photo_is_private_and_only_visible_to_its_audience(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->actingAs($this->author, 'student')->post(route('portal.community.posts.store'), [
            'body' => 'Synthetic members-only image', 'audience' => 'org',
            'photo' => UploadedFile::fake()->createWithContent('scope.png', $png),
        ])->assertRedirect(route('portal.community'));
        $post = CommunityPost::where('student_id', $this->author->id)->sole();
        Storage::disk('public')->assertMissing($post->image_path);
        $this->actingAs($this->member, 'student')->get(route('portal.community.posts.image', $post))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->outsider, 'student')->get(route('portal.community.posts.image', $post))->assertNotFound();
    }

    public function test_existing_images_remain_available_through_the_same_audience_check(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('community/legacy.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $post = $this->postRecord('Synthetic legacy members image', 'org');
        $post->forceFill(['image_path' => 'community/legacy.png'])->save();
        $response = $this->actingAs($this->member, 'student')->get(route('portal.community.posts.image', $post))->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->actingAs($this->outsider, 'student')->get(route('portal.community.posts.image', $post))->assertNotFound();
    }

    public function test_liker_details_report_real_relations_not_legacy_placeholder_counts(): void
    {
        $post = $this->postRecord('Synthetic drifted count');
        $post->forceFill(['likes_count' => 9, 'comments_count' => 8])->save();
        $this->actingAs($this->member, 'student')->getJson(route('portal.community.posts.likers', $post))
            ->assertOk()->assertJsonPath('likes_count', 0)->assertJsonPath('comments_count', 0)->assertJsonPath('likers', []);
    }

    public function test_feed_sorts_use_actual_relations_and_intersect_search_audience_and_period_filters(): void
    {
        $recent = $this->postRecord('feed-sort-intersection newest');
        $recent->forceFill(['created_at' => now()->subHour(), 'likes_count' => 900, 'comments_count' => 800])->save();
        $commented = $this->postRecord('feed-sort-intersection most comments');
        $commented->forceFill(['created_at' => now()->subHours(2)])->save();
        $liked = $this->postRecord('feed-sort-intersection most liked');
        $liked->forceFill(['created_at' => now()->subHours(3)])->save();

        $commented->comments()->createMany([
            ['student_id' => $this->member->id, 'body' => 'First real comment'],
            ['student_id' => $this->member->id, 'body' => 'Second real comment'],
            ['student_id' => $this->author->id, 'body' => 'Third real comment'],
        ]);
        $liked->comments()->create(['student_id' => $this->member->id, 'body' => 'One real comment']);
        $commented->likes()->create(['student_id' => $this->member->id, 'reaction' => 'like']);
        $liked->likes()->createMany([
            ['student_id' => $this->member->id, 'reaction' => 'love'],
            ['student_id' => $this->author->id, 'reaction' => 'like'],
        ]);

        $this->postRecord('feed-sort-intersection private', 'org');
        $this->postRecord('feed-sort-intersection department', 'department');
        $this->postRecord('feed-sort-intersection too old')->forceFill(['created_at' => now()->subDays(7)->subSecond()])->save();
        $this->postRecord('feed-sort-intersection future')->forceFill(['created_at' => now()->addSecond()])->save();
        $this->postRecord('Unrelated public record');

        $orders = [
            'recent' => [$recent->id, $commented->id, $liked->id],
            'top' => [$commented->id, $liked->id, $recent->id],
            'popular' => [$liked->id, $commented->id, $recent->id],
        ];
        foreach ($orders as $sort => $expected) {
            $response = $this->actingAs($this->outsider, 'student')->get(route('portal.community', [
                'community_q' => 'feed-sort-intersection',
                'community_audience' => 'public',
                'community_period' => 'week',
                'sort' => $sort,
            ]))->assertOk();
            $posts = $response->viewData('posts');
            $this->assertSame($expected, $posts->pluck('id')->all());
            $this->assertSame(3, $posts->total());
            $this->assertSame($sort, $response->viewData('feedSort'));
            $this->assertSame('public', $response->viewData('communityAudience'));
            $this->assertSame('week', $response->viewData('communityPeriod'));
            $byId = $posts->getCollection()->keyBy('id');
            $this->assertSame(0, (int) $byId[$recent->id]->actual_likes_count);
            $this->assertSame(0, (int) $byId[$recent->id]->actual_comments_count);
            $this->assertSame(3, (int) $byId[$commented->id]->actual_comments_count);
            $this->assertSame(1, (int) $byId[$commented->id]->actual_likes_count);
            $this->assertSame(1, (int) $byId[$liked->id]->actual_comments_count);
            $this->assertSame(2, (int) $byId[$liked->id]->actual_likes_count);
        }
    }

    public function test_audience_filters_select_public_department_and_org_without_exposing_foreign_audiences(): void
    {
        $this->outsider->forceFill(['college' => 'Foreign Fixture College'])->save();
        $public = $this->postRecord('feed-audience-fixture public');
        $department = $this->postRecord('feed-audience-fixture department', 'department');
        $organization = $this->postRecord('feed-audience-fixture organization', 'org');
        $this->postRecord('feed-audience-fixture foreign organization', 'org')
            ->forceFill(['student_id' => $this->outsider->id])->save();
        $this->postRecord('feed-audience-fixture foreign department', 'department')
            ->forceFill(['student_id' => $this->outsider->id])->save();

        $expectedByAudience = [
            'all' => [$organization->id, $department->id, $public->id],
            'public' => [$public->id],
            'department' => [$department->id],
            'org' => [$organization->id],
        ];
        foreach ($expectedByAudience as $audience => $expected) {
            $response = $this->actingAs($this->member, 'student')->get(route('portal.community', [
                'community_q' => 'feed-audience-fixture',
                'community_audience' => $audience,
            ]))->assertOk();
            $this->assertSame($expected, $response->viewData('posts')->pluck('id')->all());
            $this->assertSame($audience, $response->viewData('communityAudience'));
            $this->assertSame('all', $response->viewData('communityPeriod'));
        }
    }

    public function test_audience_filters_preserve_owner_visibility_with_incomplete_profile_data(): void
    {
        $this->member->forceFill(['org_id' => null, 'college' => null])->save();
        $ownOrg = $this->postRecord('feed-owner-fixture own organization', 'org');
        $ownOrg->forceFill(['student_id' => $this->member->id])->save();
        $ownDepartment = $this->postRecord('feed-owner-fixture own department', 'department');
        $ownDepartment->forceFill(['student_id' => $this->member->id])->save();
        $this->postRecord('feed-owner-fixture foreign organization', 'org');
        $this->postRecord('feed-owner-fixture foreign department', 'department');

        foreach (['org' => $ownOrg, 'department' => $ownDepartment] as $audience => $post) {
            $response = $this->actingAs($this->member, 'student')->get(route('portal.community', [
                'community_q' => 'feed-owner-fixture',
                'community_audience' => $audience,
            ]))->assertOk();
            $this->assertSame([$post->id], $response->viewData('posts')->pluck('id')->all());
        }
    }

    public function test_missing_and_unknown_feed_filters_normalize_to_all(): void
    {
        $public = $this->postRecord('feed-normalization-fixture public');
        $organization = $this->postRecord('feed-normalization-fixture org', 'org');
        foreach ([
            [],
            ['community_audience' => 'unknown', 'community_period' => 'year'],
            ['community_audience' => ['public'], 'community_period' => ['day']],
        ] as $filters) {
            $response = $this->actingAs($this->member, 'student')->get(route('portal.community', [
                'community_q' => 'feed-normalization-fixture',
            ] + $filters))->assertOk();
            $this->assertSame('all', $response->viewData('communityAudience'));
            $this->assertSame('all', $response->viewData('communityPeriod'));
            $this->assertSame([$organization->id, $public->id], $response->viewData('posts')->pluck('id')->all());
        }
    }

    public function test_period_filters_use_rolling_days_and_exclude_future_creation_times(): void
    {
        foreach (['day' => 1, 'week' => 7, 'month' => 30] as $period => $days) {
            $needle = 'feed-rolling-'.$period;
            $boundary = now()->subDays($days);
            $this->postRecord($needle.' outside')->forceFill(['created_at' => $boundary->copy()->subSecond()])->save();
            $atBoundary = $this->postRecord($needle.' exact lower bound');
            $atBoundary->forceFill(['created_at' => $boundary])->save();
            $inside = $this->postRecord($needle.' just inside');
            $inside->forceFill(['created_at' => $boundary->copy()->addSecond()])->save();
            $atNow = $this->postRecord($needle.' exact upper bound');
            $this->postRecord($needle.' future')->forceFill(['created_at' => now()->addSecond()])->save();

            $response = $this->actingAs($this->member, 'student')->get(route('portal.community', [
                'community_q' => $needle,
                'community_period' => $period,
            ]))->assertOk();
            $this->assertSame([$atNow->id, $inside->id, $atBoundary->id], $response->viewData('posts')->pluck('id')->all());
            $this->assertSame(3, $response->viewData('posts')->total());
            $this->assertSame($period, $response->viewData('communityPeriod'));
        }
    }

    public function test_feed_pagination_has_stable_ties_and_preserves_every_selected_filter(): void
    {
        $ids = [];
        for ($index = 0; $index < 12; $index++) {
            $post = $this->postRecord('feed-stable-pages public '.$index);
            $ids[] = $post->id;
            $this->postRecord('feed-stable-pages excluded organization '.$index, 'org');
            $this->postRecord('Unrelated public '.$index);
        }
        $ids = array_reverse($ids);

        foreach (['recent', 'top', 'popular'] as $sort) {
            $filters = [
                'community_q' => 'feed-stable-pages',
                'community_audience' => 'public',
                'community_period' => 'day',
                'sort' => $sort,
            ];
            $first = $this->actingAs($this->member, 'student')->get(route('portal.community', $filters))->assertOk();
            $posts = $first->viewData('posts');
            $this->assertSame(12, $posts->total());
            $this->assertSame(array_slice($ids, 0, 10), $posts->pluck('id')->all());
            parse_str((string) parse_url($posts->url(2), PHP_URL_QUERY), $pageQuery);
            foreach ($filters as $key => $value) {
                $this->assertSame($value, $pageQuery[$key] ?? null);
            }
            $this->assertSame('2', $pageQuery['page'] ?? null);
            $second = $this->get(route('portal.community', $filters + ['page' => 2]))->assertOk();
            $this->assertSame(array_slice($ids, 10), $second->viewData('posts')->pluck('id')->all());
        }
    }
}
