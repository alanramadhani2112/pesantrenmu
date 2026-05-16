<?php

namespace Tests\Unit;

use Tests\TestCase;

class RejectionConfigTest extends TestCase
{
    public function test_rejection_limit_defaults_to_3(): void
    {
        $this->assertEquals(3, config('akreditasi.rejection_limit'));
    }

    public function test_perbaikan_deadline_days_defaults_to_14(): void
    {
        $this->assertEquals(14, config('akreditasi.perbaikan_deadline_days'));
    }

    public function test_perbaikan_reminder_days_before_defaults_to_3(): void
    {
        $this->assertEquals(3, config('akreditasi.perbaikan_reminder_days_before'));
    }

    public function test_final_rejection_categories_returns_expected_array(): void
    {
        $expected = [
            'nilai_tidak_memenuhi' => 'Nilai Tidak Memenuhi Standar',
            'laporan_tidak_lengkap' => 'Laporan Visitasi Tidak Lengkap',
            'kartu_kendali_tidak_sesuai' => 'Kartu Kendali Tidak Sesuai',
            'inkonsistensi_data' => 'Inkonsistensi Data',
            'lainnya' => 'Lainnya',
        ];

        $this->assertEquals($expected, config('akreditasi.final_rejection_categories'));
    }
}
