<?php

namespace Tests\Concerns;

use App\Models\User;

trait AuthHelper
{
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->asAdmin()->create();
        $this->actingAs($admin);
        return $admin;
    }

    protected function actingAsAsesor(): User
    {
        $asesor = User::factory()->asAsesor()->create();
        $this->actingAs($asesor);
        return $asesor;
    }

    protected function actingAsPesantren(): User
    {
        $pesantren = User::factory()->asPesantren()->create();
        $this->actingAs($pesantren);
        return $pesantren;
    }

    protected function actingAsSuperAdmin(): User
    {
        $sa = User::factory()->asSuperAdmin()->create();
        $this->actingAs($sa);
        return $sa;
    }
}
