<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_returns_a_token_for_the_new_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'ana@example.com');
        $this->withToken($response->json('token'))->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_protected_routes_answer_401_without_a_token(): void
    {
        $this->get('/api/v1/accounts')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_logout_revokes_the_token_used_in_the_request(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
