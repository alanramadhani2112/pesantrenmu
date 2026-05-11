<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            1 => 'admin',
            2 => 'asesor',
            3 => 'pesantren',
        ];

        foreach ($roles as $id => $role) {
            \App\Models\Role::updateOrCreate(['id' => $id], ['name' => $role]);
        }
    }
}
