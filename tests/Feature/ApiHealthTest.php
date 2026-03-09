<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_health_check(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    public function test_api_routes_are_accessible(): void
    {
        // Test public routes
        $response = $this->postJson('/api/register', []);
        $response->assertStatus(422); // Validation error expected

        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422); // Validation error expected
    }

    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);

        $response = $this->getJson('/api/wallet');
        $response->assertStatus(401);

        $response = $this->getJson('/api/games');
        $response->assertStatus(401);
    }
}