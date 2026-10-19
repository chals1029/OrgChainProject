<?php

namespace Tests\Unit;

use App\Models\CommunityPost;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommunityPostFreshnessTest extends TestCase
{
    private mixed $originalTestNow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTestNow = Carbon::getTestNow();
        Carbon::setTestNow('2030-05-01 12:00:00');
    }

    protected function tearDown(): void
    {
        try {
            Carbon::setTestNow($this->originalTestNow);
        } finally {
            parent::tearDown();
        }
    }

    #[DataProvider('creationTimes')]
    public function test_new_badge_uses_only_creation_time_within_the_last_24_hours(?string $createdAt, bool $expected): void
    {
        $post = (new CommunityPost)->forceFill([
            'created_at' => $createdAt,
            'updated_at' => now(),
        ]);

        $this->assertSame($expected, $post->isNewInFeed());
    }

    public static function creationTimes(): array
    {
        return [
            'missing creation time despite a recent update' => [null, false],
            'one second in the future' => ['2030-05-01 12:00:01', false],
            'created now' => ['2030-05-01 12:00:00', true],
            'one second ago' => ['2030-05-01 11:59:59', true],
            'one second newer than the cutoff' => ['2030-04-30 12:00:01', true],
            'exactly 24 hours old' => ['2030-04-30 12:00:00', false],
            'one second older than the cutoff' => ['2030-04-30 11:59:59', false],
            'three weeks old despite a recent update' => ['2030-04-10 12:00:00', false],
        ];
    }

    public function test_checking_freshness_does_not_mutate_timestamps_or_the_clock(): void
    {
        $post = (new CommunityPost)->forceFill([
            'created_at' => '2030-04-30 12:00:01',
            'updated_at' => '2030-05-01 11:00:00',
        ]);
        $post->syncOriginal();
        $attributes = $post->getAttributes();
        $createdAt = $post->created_at->toISOString();
        $updatedAt = $post->updated_at->toISOString();
        $frozenNow = now()->toISOString();

        $this->assertTrue($post->isNewInFeed());
        $this->assertTrue($post->isNewInFeed());

        $this->assertSame($attributes, $post->getAttributes());
        $this->assertSame($createdAt, $post->created_at->toISOString());
        $this->assertSame($updatedAt, $post->updated_at->toISOString());
        $this->assertSame([], $post->getDirty());
        $this->assertSame($frozenNow, now()->toISOString());
    }
}
