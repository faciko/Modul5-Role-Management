<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // '/' di W05 mengalihkan tamu ke /login.
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
