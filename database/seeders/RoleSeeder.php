<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the four canonical roles. Super admin (id=4) is the god-mode
     * role and is mutually exclusive with admin (id=1).
     */
    public function run(): void
    {
        $roles = [
            1 => 'admin',
            2 => 'asesor',
            3 => 'pesantren',
            4 => 'super_admin',
        ];

        foreach ($roles as $id => $name) {
            Role::updateOrCreate(['id' => $id], ['name' => $name]);
        }
    }
}
