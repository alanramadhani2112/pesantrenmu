<?php

namespace Tests\Unit;

use Tests\TestCase;

class AkreditasiTimeoutConfigTest extends TestCase
{
    public function test_assessment_default_duration_days_defaults_to_30(): void
    {
        $this->assertEquals(30, config('akreditasi-timeout.assessment.default_duration_days'));
    }

    public function test_visitasi_default_duration_days_defaults_to_14(): void
    {
        $this->assertEquals(14, config('akreditasi-timeout.visitasi.default_duration_days'));
    }

    public function test_reminder_days_before_deadline_defaults_to_3(): void
    {
        $this->assertEquals(3, config('akreditasi-timeout.reminder.days_before_deadline'));
    }

    public function test_escalation_interval_days_defaults_to_1(): void
    {
        $this->assertEquals(1, config('akreditasi-timeout.escalation.interval_days'));
    }
}
