<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\MasterEdpmButir;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_demo_accounts_and_master_edpm_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedUsers = [
            'admin@spm.test' => [1, 'admin'],
            'asesor@spm.test' => [2, 'asesor'],
            'pesantren@spm.test' => [3, 'pesantren'],
        ];

        foreach ($expectedUsers as $email => [$roleId, $roleName]) {
            $user = User::with('role')->where('email', $email)->first();

            $this->assertNotNull($user);
            $this->assertSame($roleId, $user->role_id);
            $this->assertSame($roleName, $user->role->name);
            $this->assertSame(1, $user->status);
            $this->assertTrue(Hash::check('password', $user->password));
        }

        $this->assertGreaterThan(0, MasterEdpmButir::count());
        $this->assertDatabaseHas('akreditasis', [
            'user_id' => User::where('email', 'pesantren@spm.test')->value('id'),
            'status' => 6,
        ]);
        $this->assertSame(1, Akreditasi::query()->count());
    }
}
