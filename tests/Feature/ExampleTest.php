<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_homepage_uses_k_connect_branding(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('K-Connect');
    }
}
