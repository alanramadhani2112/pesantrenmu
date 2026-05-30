<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AkreditasiRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_akreditasi_no_longer_has_legacy_submission_chain_column(): void
    {
        $this->assertFalse(Schema::hasColumn('akreditasis', 'parent'));
        $this->assertFalse(method_exists(Akreditasi::class, 'parentAkreditasi'));
        $this->assertFalse(method_exists(Akreditasi::class, 'children'));
    }

    public function test_akreditasi_can_still_be_created_without_legacy_chain_data(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 6,
        ]);

        $this->assertNotNull($akreditasi->id);
        $this->assertSame($user->id, $akreditasi->user_id);
    }
}
