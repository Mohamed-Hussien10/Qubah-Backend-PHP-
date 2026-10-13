<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityAndAuthTest extends TestCase
{
    /**
     * Test that unauthenticated API requests return 401 instead of 500,
     * even without the 'Accept: application/json' header.
     */
    public function test_unauthenticated_request_returns_401_without_accept_header(): void
    {
        // Request a protected route without Accept: application/json
        $response = $this->withHeaders([])->get('/api/v1/auth/me');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
            'error' => 'unauthenticated',
        ]);
    }

    /**
     * Test path traversal attacks on thumbnail endpoint are blocked.
     */
    public function test_thumbnail_path_traversal_is_blocked(): void
    {
        $response = $this->get('/api/v1/thumbnails/../../../../.env');
        $response->assertStatus(403);
    }

    /**
     * Test path traversal attacks on files proxy endpoint are blocked.
     */
    public function test_files_path_traversal_is_blocked(): void
    {
        $response = $this->get('/api/v1/files/../../../../.env');
        $response->assertStatus(403);
    }

    /**
     * Test that named login route exists and returns 401 unauthenticated JSON.
     */
    public function test_named_login_route_returns_401(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(401);
    }
}
