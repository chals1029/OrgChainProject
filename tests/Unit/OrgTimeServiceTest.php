<?php

namespace Tests\Unit;

use App\Services\OrgTimeService;
use Tests\TestCase;

class OrgTimeServiceTest extends TestCase
{
    public function test_upload_time_is_rendered_in_the_official_philippine_timezone(): void
    {
        config(['app.timezone' => 'Asia/Manila']);

        $this->assertSame(
            'Sep 21, 2026 4:43 PM PHT (UTC+8)',
            OrgTimeService::format('2026-09-21T08:43:15+00:00'),
        );
    }

    public function test_invalid_upload_time_does_not_break_the_page(): void
    {
        $this->assertSame('', OrgTimeService::format('not-a-timestamp'));
    }
}
