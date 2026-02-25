<?php

namespace Tests\Feature\v1;

use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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
     * Profile info route must return role key
     */
    public function test_info_route_returns_role(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $user = User::factory()->create($userData);
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price' => 0, 'size' => 0]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        $loginResponse = $this->actingAsGuest()
                        ->post(route('api.v1.auth.tokens.new'), $userData);
        $loginResponse->assertStatus(200);

        $token = collect($loginResponse->json())->get('token');

        $infoResponse = $this->get(route('api.v1.auth.me'), [
                            'Authorization' => "Bearer {$token}",
                        ]);
        $infoResponse->assertStatus(200);

        $infoData = collect($infoResponse->json());

        $this->assertTrue($infoData->has('role'));
    }

    /**
     * Login route must return plan key in response
     */
    public function test_login_return_important_keys(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $user = User::factory()->create($userData);
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price' => 0, 'size' => 0]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        $response = $this->actingAsGuest()
                        ->post(route('api.v1.auth.tokens.new'), $userData);
        $response->assertStatus(200);

        $responseData = collect($response->json());

        $this->assertTrue($responseData->has('plan'));
        $this->assertTrue($responseData->has('profile'));
        $this->assertTrue($responseData->has('role'));
        $this->assertTrue($responseData->has('token'));
        $this->assertTrue($responseData->has('used_disk'));
    }

    /**
     * User can revoke their current token
     */
    public function test_token_can_be_revoked(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $user = User::factory()->create($userData);
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price' => 0, 'size' => 0]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

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

        $response = $this->actingAsGuest()
                    ->post(route('api.v1.auth.new'), $userData);
        $response->assertStatus(422);

        $this->assertDatabaseCount(User::class, 1);
    }

    /**
     * Validation works as expected
     */
    public function test_login_validation_working_as_expected(): void
    {
        $response = $this->post(route('api.v1.auth.new'), [
                        'email' => '',
                        'password' => '',
                    ]);
        $response->assertStatus(422);

        $errors = collect($response->json('errors'));

        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('password'));
    }
}
