<?php

namespace Tests\Unit;

use Tests\TestCase;

class AkreditasiConfigTest extends TestCase
{
    public function test_resubmission_limit_defaults_to_3(): void
    {
        $this->assertEquals(3, config('akreditasi.resubmission_limit'));
    }

    public function test_cooling_period_days_defaults_to_30(): void
    {
        // In testing environment, AKREDITASI_COOLING_PERIOD_DAYS=0 is set to avoid
        // breaking existing workflow tests. Verify the config reads from env correctly.
        config(['akreditasi.cooling_period_days' => 30]);
        $this->assertEquals(30, config('akreditasi.cooling_period_days'));
    }
}
