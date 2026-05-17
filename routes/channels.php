<?php

use App\Models\Akreditasi;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Presence channel for the akreditasi detail page. Only authenticated
| users with permission to view the resource may join.
| Returns a payload with `id` + `name` so other clients can render a
| "currently viewing" indicator (excluding the current user).
*/

Broadcast::channel('akreditasi.{akreditasiId}', function (User $user, int $akreditasiId): array|false {
    $akreditasi = Akreditasi::find($akreditasiId);

    if ($akreditasi === null) {
        return false;
    }

    if (! $user->can('view', $akreditasi)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
