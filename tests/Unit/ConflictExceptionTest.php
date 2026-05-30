<?php

namespace Tests\Unit;

use App\Exceptions\ConflictException;
use Tests\TestCase;

class ConflictExceptionTest extends TestCase
{
    /**
     * Test that ConflictException can be constructed with required parameters.
     */
    public function test_construction_with_required_parameters(): void
    {
        $exception = new ConflictException(42, 3);

        $this->assertInstanceOf(ConflictException::class, $exception);
        $this->assertInstanceOf(\DomainException::class, $exception);
        $this->assertEquals(42, $exception->akreditasiId);
        $this->assertEquals(3, $exception->currentStatus);
        $this->assertEquals('Record telah dimodifikasi oleh pengguna lain.', $exception->getMessage());
    }

    /**
     * Test that ConflictException can be constructed with a custom message.
     */
    public function test_construction_with_custom_message(): void
    {
        $exception = new ConflictException(10, 5, 'Custom conflict message');

        $this->assertEquals(10, $exception->akreditasiId);
        $this->assertEquals(5, $exception->currentStatus);
        $this->assertEquals('Custom conflict message', $exception->getMessage());
    }

    /**
     * Test getStatusLabel() returns correct label for status 1 (Validasi Admin).
     */
    public function test_get_status_label_for_status_1(): void
    {
        $exception = new ConflictException(1, 1);
        $this->assertEquals('Validasi Admin', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status 2 (Penilaian Pasca Visitasi).
     */
    public function test_get_status_label_for_status_2(): void
    {
        $exception = new ConflictException(1, 2);
        $this->assertEquals('Penilaian Pasca Visitasi', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status 3 (Visitasi).
     */
    public function test_get_status_label_for_status_3(): void
    {
        $exception = new ConflictException(1, 3);
        $this->assertEquals('Visitasi', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status 4 (Review Asesor).
     */
    public function test_get_status_label_for_status_4(): void
    {
        $exception = new ConflictException(1, 4);
        $this->assertEquals('Review Asesor', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status 5 (Verifikasi Berkas).
     */
    public function test_get_status_label_for_status_5(): void
    {
        $exception = new ConflictException(1, 5);
        $this->assertEquals('Verifikasi Berkas', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status 6 (Pengajuan).
     */
    public function test_get_status_label_for_status_6(): void
    {
        $exception = new ConflictException(1, 6);
        $this->assertEquals('Pengajuan', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status 0 (Selesai).
     */
    public function test_get_status_label_for_status_0(): void
    {
        $exception = new ConflictException(1, 0);
        $this->assertEquals('Selesai', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status -1 (Ditolak).
     */
    public function test_get_status_label_for_status_negative_1(): void
    {
        $exception = new ConflictException(1, -1);
        $this->assertEquals('Ditolak', $exception->getStatusLabel());
    }

    /**
     * Test getStatusLabel() returns correct label for status -2 (Banding).
     */
    public function test_get_status_label_for_status_negative_2(): void
    {
        $exception = new ConflictException(1, -2);
        $this->assertEquals('Banding', $exception->getStatusLabel());
    }
}
