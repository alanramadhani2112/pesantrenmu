<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait AssertionHelper
{
    protected function assertSuccessfulResponse(TestResponse $response): TestResponse
    {
        return $response->assertOk();
    }

    protected function assertForbidden(TestResponse $response): TestResponse
    {
        return $response->assertForbidden();
    }

    protected function assertRedirectedToRoute(TestResponse $response, string $routeName): TestResponse
    {
        return $response->assertRedirectToRoute($routeName);
    }
}
