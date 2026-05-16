<?php

namespace Tests\Unit;

use Tests\TestCase;

class BandingConfigTest extends TestCase
{
    public function test_banding_limit_defaults_to_1(): void
    {
        $this->assertEquals(1, config('akreditasi.banding_limit'));
    }

    public function test_banding_review_days_defaults_to_14(): void
    {
        $this->assertEquals(14, config('akreditasi.banding_review_days'));
    }

    public function test_banding_reminder_days_before_defaults_to_3(): void
    {
        $this->assertEquals(3, config('akreditasi.banding_reminder_days_before'));
    }
}
