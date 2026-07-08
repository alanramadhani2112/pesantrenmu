<?php

namespace Tests\Feature;

use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterEdpmSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_edpm_edit_button_serializes_js_sensitive_text_safely(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen Aman', 'ipr' => false]);
        $payload = "Butir `);window.__xss=1;// \${alert('x')} \" quote ' single";

        MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => 'SK-1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => $payload,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.master-edpm'))
            ->assertOk()
            ->getContent();

        preg_match('/x-on:click="(openButirModal\(1, 1,[^\n]*\))"/', $html, $matches);
        $this->assertNotEmpty($matches, 'Expected edit-button handler to be present.');

        $handler = html_entity_decode($matches[1], ENT_QUOTES);

        $this->assertStringNotContainsString('`);window.__xss=1;//', $handler);
        $this->assertStringContainsString('\\u0027x\\u0027', $handler);
    }
}
