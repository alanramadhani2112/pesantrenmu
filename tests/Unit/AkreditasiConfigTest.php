<?php

namespace Tests\Unit;

use Tests\TestCase;

class AkreditasiConfigTest extends TestCase
{
    public function test_banding_limit_defaults_to_1(): void
    {
        $this->assertEquals(1, config('akreditasi.banding_limit'));
    }

    public function test_perbaikan_deadline_defaults_to_14_days(): void
    {
        $this->assertEquals(14, config('akreditasi.perbaikan_deadline_days'));
    }
}
