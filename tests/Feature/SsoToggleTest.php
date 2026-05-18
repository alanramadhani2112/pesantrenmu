<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the SSO_ENABLED toggle (Task 1.3 / Task 7.6).
 *
 * When SSO_ENABLED=false the login page must NOT contain the SSO login
 * button/link.  When SSO_ENABLED=true the button must be present.
 */
class SsoToggleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Task 7.6: When SSO is disabled, the login page does NOT show the
     * "Login via Muhammadiyah ID" button.
     */
    public function test_sso_login_button_not_shown_when_sso_disabled(): void
    {
        config(['sso.enabled' => false]);

        $response = $this->get(route('login'));

        // The SSO preflight route must not appear anywhere on the page
        $response->assertDontSee(route('sso.preflight'), false);
    }

    /**
     * Task 7.6 (positive case): When SSO is enabled, the login page DOES
     * show the "Login via Muhammadiyah ID" button.
     */
    public function test_sso_login_button_shown_when_sso_enabled(): void
    {
        config(['sso.enabled' => true]);

        $response = $this->get(route('login'));

        $response->assertSee(route('sso.preflight'), false);
    }

    /**
     * Task 7.6: SSO disabled — the login page must not contain any reference
     * to the sso.preflight route, regardless of how the link is rendered.
     */
    public function test_login_page_has_no_sso_link_when_disabled(): void
    {
        config(['sso.enabled' => false]);

        $response = $this->get(route('login'));

        // The SSO preflight URL must not appear in the page source
        $response->assertDontSee('sso/preflight', false);
        // The Muhammadiyah ID login image/alt text must not appear
        $response->assertDontSee('Login via Muhammadiyah ID', false);
    }
}
