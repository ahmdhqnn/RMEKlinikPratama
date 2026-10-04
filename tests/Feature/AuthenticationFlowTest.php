<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_sign_in_and_disabled_user_is_rejected(): void
    {
        $activeUser = User::factory()->create([
            'email' => 'doctor@example.test',
            'password' => 'SecretPassword123',
            'role' => 'dokter',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $activeUser->email,
            'password' => 'SecretPassword123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($activeUser);

        auth()->logout();
        $disabledUser = User::factory()->create([
            'email' => 'disabled@example.test',
            'password' => 'AnotherSecret123',
            'role' => 'dokter',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $disabledUser->email,
            'password' => 'AnotherSecret123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('audit_logs', ['user_id' => $disabledUser->id, 'action' => 'LOGIN']);
    }

    public function test_administrator_login_uses_role_dashboard_instead_of_forbidden_intended_url(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'SecretPassword123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('login.store'), [
                'email' => $administrator->email,
                'password' => 'SecretPassword123',
            ])
            ->assertRedirect(route('admin.index'));

        $this->get(route('admin.index'))->assertOk();
        $this->get(route('dashboard'))->assertForbidden();
    }
}
