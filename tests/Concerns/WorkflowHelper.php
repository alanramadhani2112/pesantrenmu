<?php

namespace Tests\Concerns;

use App\Models\Akreditasi;
use App\Models\Pesantren;

trait WorkflowHelper
{
    protected function createAkreditasiInState(int $state = Akreditasi::STATUS_PENGAJUAN): Akreditasi
    {
        return Akreditasi::factory()->status($state)->create();
    }

    protected function createAkreditasiWithRelations(int $state = Akreditasi::STATUS_PENGAJUAN): Akreditasi
    {
        $pesantren = Pesantren::factory()->create();
        return Akreditasi::factory()
            ->status($state)
            ->for($pesantren)
            ->create();
    }
}
