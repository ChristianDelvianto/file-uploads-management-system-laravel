<?php

namespace Tests\Feature\v1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guest can issue a new access token
     */
    public function test_guest_can_issue_token(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        User::factory()->create($userData);

        $response = $this->actingAsGuest()
                    ->post(route('api.v1.auth.tokens.new'), $userData);

        $response->assertStatus(200);
        $responseData = collect($response->json());

        $this->assertTrue($responseData->has('token'));
    }

    /**
     * Guest must not be able to retrieve user info
     */
    public function test_guest_cannot_fetch_profile_info(): void
    {
        $response = $this->actingAsGuest()
                    ->get(route('api.v1.auth.me'));

        $response->assertStatus(401);
    }

    /**
     * Revoke token route must not be accessable by guest
     */
    public function test_guest_cannot_access_revoke_token_route(): void
    {
        $response = $this->actingAsGuest()
                    ->delete(route('api.v1.auth.tokens.delete'));

        $response->assertStatus(401);
    }

    /**
     * User can revoke their current token
     */
    public function test_token_can_be_revoke(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        User::factory()->create($userData);

        $loginResponse = $this->actingAsGuest()
                        ->post(route('api.v1.auth.tokens.new'), $userData);
        $loginResponse->assertStatus(200);

        $loginData = collect($loginResponse->json());

        $this->assertTrue($loginData->has('token'));

        $token = $loginData->get('token');

        $revokeTokenResponse = $this->delete(route('api.v1.auth.tokens.delete'), [], [
                                    'Authorization' => "Bearer {$token}",
                                ]);
        $revokeTokenResponse->assertStatus(204);
    }

    /**
     * Ensure email uniqueness in users table
     */
    public function test_users_are_unique_by_email(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        User::factory()->create($userData);

        $response = $this->post(route('api.v1.auth.new'), $userData);

        $response->assertStatus(422);

        $this->assertDatabaseCount(User::class, 1);
    }

    /**
     * 
     */
    // public function test_revoked_token_cannot_be_reuse(): void
    // {
    //     User::factory()->create([
    //         'email' => 'test@example.com',
    //         'password' => 'password'
    //     ]);

    //     $response = $this->actingAsGuest()
    //                 ->post(route('api.v1.auth.tokens.new'), [
    //                     'email' => 'test@example.com',
    //                     'password' => 'password',
    //                 ]);
    //     $response->assertStatus(200);
    //     $responseData = collect($response->json());

    //     $this->assertTrue($responseData->has('token'));

    //     $token = $responseData->get('token');

    //     $response2 = $this->delete(route('api.v1.auth.tokens.delete'), [], [
    //                     'Authorization' => "Bearer {$token}",
    //                 ]);
    //     $response2->assertStatus(204);

    //     $response3 = $this->get(route('api.v1.auth.me'), [
    //                     'Authorization' => "Bearer {$token}",
    //                 ]);
    //     $response3->assertStatus(401);
    // }
}
