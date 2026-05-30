<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class PresencePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_channel_returns_user_payload_for_authorized_user(): void
    {
        [$user, $akreditasi] = $this->makeOwnerAndAkreditasi();

        $payload = $this->resolveChannelPayload($user, $akreditasi->id);

        $this->assertIsArray($payload);
        $this->assertSame($user->id, $payload['id']);
        $this->assertSame($user->name, $payload['name']);
    }

    public function test_channel_rejects_user_without_view_permission(): void
    {
        [, $akreditasi] = $this->makeOwnerAndAkreditasi();
        [$foreigner] = $this->makePesantrenUser();

        $payload = $this->resolveChannelPayload($foreigner, $akreditasi->id);

        $this->assertFalse($payload);
    }

    public function test_channel_rejects_unknown_akreditasi(): void
    {
        [$user] = $this->makeOwnerAndAkreditasi();

        $payload = $this->resolveChannelPayload($user, 999999);

        $this->assertFalse($payload);
    }

    /**
     * Property 5 - server returns the current user's identity so the
     * client (Alpine x-data init in presence-indicator.blade.php) can
     * filter `currentUserId` out of the displayed presence list.
     */
    public function test_payload_returns_current_user_identity_for_client_side_exclusion(): void
    {
        [$user, $akreditasi] = $this->makeOwnerAndAkreditasi();

        $payload = $this->resolveChannelPayload($user, $akreditasi->id);

        $this->assertSame(
            $user->id,
            $payload['id'],
            'Server must echo current user id so the client can exclude self.'
        );
        $this->assertSame($user->name, $payload['name']);
    }

    /**
     * @return array{0: User, 1: Akreditasi}
     */
    private function makeOwnerAndAkreditasi(): array
    {
        [$user] = $this->makePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        return [$user, $akreditasi];
    }

    /**
     * @return array{0: User, 1: Pesantren}
     */
    private function makePesantrenUser(): array
    {
        $user = User::factory()->create([
            'role_id' => Role::ID_PESANTREN,
        ])->fresh(['role.permissions']);

        $pesantren = Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren '.$user->id,
        ]);

        return [$user, $pesantren];
    }

    private function resolveChannelPayload(User $user, int $akreditasiId): mixed
    {
        $broadcaster = Broadcast::driver();

        $reflected = new \ReflectionObject($broadcaster);
        $channelsProperty = $reflected->getProperty('channels');
        $channelsProperty->setAccessible(true);
        $channels = $channelsProperty->getValue($broadcaster);

        $callback = $channels['akreditasi.{akreditasiId}'] ?? null;

        if ($callback === null) {
            $this->fail('Presence channel akreditasi.{akreditasiId} is not registered.');
        }

        return $callback($user, $akreditasiId);
    }
}
