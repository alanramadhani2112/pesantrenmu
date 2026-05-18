<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure SSO server URL has trailing slash for proper URL construction
        config(['sso.server_url' => 'https://sso.example.com/']);
    }

    /**
     * Test: SSO token exchange handles connection timeout gracefully.
     * When the SSO server is down or times out during token exchange,
     * the user should be redirected to login with a flash error.
     */
    public function test_sso_auth_handles_connection_timeout_gracefully(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth/token')) {
                throw new ConnectionException('Connection timed out after 10000 milliseconds');
            }

            return Http::response('', 200);
        });

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=test-code');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
    }

    /**
     * Test: SSO user fetch handles connection timeout gracefully.
     * When the SSO server is down during user profile fetch,
     * the user should be redirected to login with a flash error.
     */
    public function test_sso_login_handles_connection_timeout_gracefully(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api/user')) {
                throw new ConnectionException('Connection timed out after 10000 milliseconds');
            }

            return Http::response('', 200);
        });

        $response = $this->withSession(['sso_access_token' => 'valid-token'])
            ->get('/sso/auth2');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
    }

    /**
     * Test: SSO auth still works normally when server responds successfully.
     */
    public function test_sso_auth_proceeds_normally_on_successful_response(): void
    {
        Http::fake([
            'https://sso.example.com/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=test-code');

        $response->assertRedirect(route('sso.login'));
        $response->assertSessionHas('sso_access_token', 'test-access-token');
    }

    /**
     * Test: SSO timeout config defaults to 10 seconds.
     */
    public function test_sso_timeout_config_defaults_to_10_seconds(): void
    {
        $this->assertEquals(10, config('sso.timeout'));
    }

    /**
     * Test: App does not crash with 500 error when SSO server is unreachable.
     * The response should always be a redirect, never a server error.
     */
    public function test_no_500_error_when_sso_server_unreachable(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'oauth/token')) {
                throw new ConnectionException('Could not resolve host');
            }

            return Http::response('', 200);
        });

        $response = $this->withSession(['state' => 'valid-state'])
            ->get('/sso/auth?state=valid-state&code=test-code');

        // Should be a redirect (302), NOT a 500 error
        $this->assertTrue(
            $response->isRedirect(),
            'Expected a redirect response, but got status: ' . $response->getStatusCode()
        );
    }
}
