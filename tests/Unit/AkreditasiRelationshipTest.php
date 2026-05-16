<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkreditasiRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_parent_akreditasi_relationship_resolves_correctly(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $child = Akreditasi::create([
            'user_id' => $user->id,
            'parent' => $parent->id,
            'status' => 6,
        ]);

        $this->assertEquals($parent->id, $child->parentAkreditasi->id);
    }

    public function test_children_relationship_resolves_correctly(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $child = Akreditasi::create([
            'user_id' => $user->id,
            'parent' => $parent->id,
            'status' => 6,
        ]);

        $this->assertTrue($parent->children->contains($child));
    }
}
