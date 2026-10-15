<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\UserAccount;
use Illuminate\Http\UploadedFile;
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
        if (! getenv('ORGCHAIN_TEST_DATABASE') || ! getenv('ORGCHAIN_TEST_ORGCHAIN_DATABASE')) {
            throw new \RuntimeException('CommunityReportIssuesTest requires isolated ORGCHAIN_TEST_DATABASE and ORGCHAIN_TEST_ORGCHAIN_DATABASE databases.');
        }
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        DB::connection('orgchain')->beginTransaction();
        DB::connection('orgchain')->table('student_organizations')->insert([
            ['org_id' => 910001, 'org_name' => 'Synthetic Community Alpha', 'status' => 'active'],
            ['org_id' => 910002, 'org_name' => 'Synthetic Community Beta', 'status' => 'active'],
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
        DB::connection('orgchain')->rollBack();
        DB::connection('mysql')->rollBack();
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
}
