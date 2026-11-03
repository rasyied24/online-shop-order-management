<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create(); // password bawaan factory: "password"

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'name', 'email']]]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();

        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // guard menyimpan user di memori selama satu test, jadi direset dulu
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }
}
