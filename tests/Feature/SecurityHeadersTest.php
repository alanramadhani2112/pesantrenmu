<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_http_dot_test_requests_skip_cross_origin_opener_policy(): void
    {
        $response = $this->get('http://spm_fix.test/login');

        $response->assertOk();

        $this->assertFalse($response->headers->has('Cross-Origin-Opener-Policy'));
    }

    public function test_https_requests_keep_cross_origin_opener_policy(): void
    {
        $response = $this->get('https://spm.example.com/login');

        $response->assertOk();
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }
}
