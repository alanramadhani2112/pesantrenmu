<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesantrenDocumentNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pesantren_documents_all_redirects_to_iapm_guide(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);

        $this->actingAs($pesantren)
            ->get('/documents/all')
            ->assertRedirect('/documents/iapm');
    }

    public function test_pesantren_kartu_kendali_document_route_redirects_to_accreditation_flow(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);

        $this->actingAs($pesantren)
            ->get('/documents/kartu_kendali')
            ->assertRedirect('/pesantren/akreditasi?focus=kartu_kendali');
    }
}
