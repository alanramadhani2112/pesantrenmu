<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Coverage: SsoController — preflight, auth (state validation, token exchange,
 * connection errors), login (session token, user fetch, status check, redirect).
 */
class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config([
            'sso.server_url' => 'https://sso.example.com/',
            'sso.client_id' => 'test-client-id',
            'sso.client_secret' => 'test-client-secret',
            'sso.redirect_url' => '/dashboard',
            'sso.timeout' => 10,
        ]);
    }

    // ─── preflight ────────────────────────────────────────────────────────────

    public function test_preflight_redirects_to_sso_authorize_url(): void
    {
        $response = $this->get(route('sso.preflight'));

        $response->assertRedirect();
        $this->assertStringContainsString(
            'https://sso.example.com/oauth/authorize',
            $response->headers->get('Location')
        );
    }

    public function test_preflight_includes_client_id_in_redirect(): void
    {
        $response = $this->get(route('sso.preflight'));

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('client_id=test-client-id', $location);
    }

    public function test_preflight_includes_response_type_code(): void
    {
        $response = $this->get(route('sso.preflight'));

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('response_type=code', $location);
    }

    public function test_preflight_stores_state_in_session(): void
    {
        $response = $this->get(route('sso.preflight'));

        $response->assertSessionHas('state');
        $state = session('state');
        $this->assertNotEmpty($state);
        $this->assertEquals(40, strlen($state));
    }

    public function test_preflight_includes_state_in_redirect_url(): void
    {
        $response = $this->get(route('sso.preflight'));

        $state = session('state');
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('state='.$state, $location);
    }

    // ─── auth: state validation ───────────────────────────────────────────────

    public function test_auth_redirects_to_login_when_state_missing_from_session(): void
    {
        $response = $this->get('/sso/auth?state=some-state&code=some-code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_auth_redirects_to_login_when_state_mismatch(): void
    {
        $response = $this->withSession(['state' => 'correct-state'])
            ->get('/sso/auth?state=wrong-state&code=some-code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid state, it may be expired, please try again');
    }

    public function test_auth_redirects_to_login_when_state_empty_in_session(): void
    {
        $response = $this->withSession(['state' => ''])
            ->get('/sso/auth?state=&code=some-code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    // ─── auth: token exchange success ────────────────────────────────────────

    public function test_auth_stores_access_token_in_session_on_success(): void
    {
        Http::fake([
            'https://sso.example.com/oauth/token' => Http::response([
                'access_token' => 'my-access-token',
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=auth-code');

        $response->assertRedirect(route('sso.login'));
        $response->assertSessionHas('sso_access_token', 'my-access-token');
    }

    public function test_auth_redirects_to_sso_login_on_success(): void
    {
        Http::fake([
            'https://sso.example.com/oauth/token' => Http::response([
                'access_token' => 'token-abc',
            ], 200),
        ]);

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=code-xyz');

        $response->assertRedirect(route('sso.login'));
    }

    // ─── auth: token exchange failure ────────────────────────────────────────

    public function test_auth_redirects_to_login_when_token_exchange_returns_error(): void
    {
        Http::fake([
            'https://sso.example.com/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=bad-code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'SSO authentication failed, please try again');
    }

    public function test_auth_redirects_to_login_when_access_token_missing_from_response(): void
    {
        Http::fake([
            'https://sso.example.com/oauth/token' => Http::response(['token_type' => 'Bearer'], 200),
        ]);

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=some-code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'SSO authentication failed, please try again');
    }

    // ─── auth: connection errors ──────────────────────────────────────────────

    public function test_auth_handles_connection_timeout_gracefully(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth/token')) {
                throw new ConnectionException('Connection timed out');
            }

            return Http::response('', 200);
        });

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
    }

    public function test_auth_never_returns_500_on_connection_error(): void
    {
        Http::fake(function (Request $request) {
            throw new ConnectionException('Could not resolve host');
        });

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=code');

        $this->assertTrue($response->isRedirect(), 'Expected redirect, got: '.$response->getStatusCode());
    }

    // ─── login: missing token ─────────────────────────────────────────────────

    public function test_login_redirects_to_login_when_no_token_in_session(): void
    {
        $response = $this->get(route('sso.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'SSO session expired, please try again');
    }

    public function test_login_redirects_to_login_when_token_is_empty_string(): void
    {
        $response = $this->withSession(['sso_access_token' => ''])
            ->get(route('sso.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'SSO session expired, please try again');
    }

    // ─── login: user fetch failure ────────────────────────────────────────────

    public function test_login_redirects_to_login_when_sso_user_fetch_fails(): void
    {
        Http::fake([
            'https://sso.example.com/api/user' => Http::response([], 401),
        ]);

        $response = $this->withSession(['sso_access_token' => 'bad-token'])
            ->get(route('sso.login'));

        $response->assertRedirect(route('login'));
    }

    public function test_login_handles_connection_timeout_on_user_fetch(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api/user')) {
                throw new ConnectionException('Connection timed out');
            }

            return Http::response('', 200);
        });

        $response = $this->withSession(['sso_access_token' => 'valid-token'])
            ->get(route('sso.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
    }

    // ─── login: disabled user ─────────────────────────────────────────────────

    public function test_login_redirects_to_login_when_user_is_disabled(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 0]);

        Http::fake([
            'https://sso.example.com/api/user' => Http::response([
                'id' => 'sso-uid-disabled',
                'name' => $user->name,
                'email' => $user->email,
                'level' => 2,
            ], 200),
        ]);

        $response = $this->withSession(['sso_access_token' => 'token'])
            ->get(route('sso.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
    }

    // ─── login: successful authentication ────────────────────────────────────

    public function test_login_authenticates_user_on_success(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 1]);

        Http::fake([
            'https://sso.example.com/api/user' => Http::response([
                'id' => 'sso-uid-ok',
                'name' => $user->name,
                'email' => $user->email,
                'level' => 2,
            ], 200),
        ]);

        $this->withSession(['sso_access_token' => 'valid-token'])
            ->get(route('sso.login'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_login_redirects_to_configured_redirect_url_on_success(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 1]);

        Http::fake([
            'https://sso.example.com/api/user' => Http::response([
                'id' => 'sso-uid-redir',
                'name' => $user->name,
                'email' => $user->email,
                'level' => 2,
            ], 200),
        ]);

        $response = $this->withSession(['sso_access_token' => 'valid-token'])
            ->get(route('sso.login'));

        $response->assertRedirect(config('sso.redirect_url'));
    }

    public function test_login_redirects_to_intended_url_when_set(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 1]);

        Http::fake([
            'https://sso.example.com/api/user' => Http::response([
                'id' => 'sso-uid-intended',
                'name' => $user->name,
                'email' => $user->email,
                'level' => 2,
            ], 200),
        ]);

        $response = $this->withSession([
            'sso_access_token' => 'valid-token',
            'intended_url' => '/pesantren/profile',
        ])->get(route('sso.login'));

        $response->assertRedirect('/pesantren/profile');
    }

    public function test_login_consumes_token_from_session(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 1]);

        Http::fake([
            'https://sso.example.com/api/user' => Http::response([
                'id' => 'sso-uid-consume',
                'name' => $user->name,
                'email' => $user->email,
                'level' => 2,
            ], 200),
        ]);

        $response = $this->withSession(['sso_access_token' => 'valid-token'])
            ->get(route('sso.login'));

        // Token should be pulled (removed) from session after use
        $response->assertSessionMissing('sso_access_token');
    }
}
