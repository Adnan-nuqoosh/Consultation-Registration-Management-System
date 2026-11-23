<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWebsiteFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_login_and_logout_securely(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('StrongPassword123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'StrongPassword123!',
            'device_name' => 'Test dashboard',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com');

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_non_admin_and_inactive_users_cannot_login(): void
    {
        $manager = User::factory()->create([
            'email' => 'manager@example.com',
            'password' => Hash::make('StrongPassword123!'),
            'role' => 'manager',
        ]);
        $inactiveAdmin = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('StrongPassword123!'),
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $manager->email,
            'password' => 'StrongPassword123!',
        ])->assertUnauthorized();

        $this->postJson('/api/auth/login', [
            'email' => $inactiveAdmin->email,
            'password' => 'StrongPassword123!',
        ])->assertUnauthorized();
    }

    public function test_logout_requires_an_authenticated_session(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
