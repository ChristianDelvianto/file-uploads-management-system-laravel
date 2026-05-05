<?php

namespace Tests\Feature\v1;

use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guest must not be able to retrieve user info
     */
    public function test_guest_cannot_fetch_profile_info(): void
    {
        $this->actingAsGuest()
            ->get(route('api.v1.auth.info'))
            ->assertUnauthorized();
    }

    /**
     * Revoke token route must not be accessable by guest
     */
    public function test_guest_cannot_access_revoke_token_route(): void
    {
        $this->actingAsGuest()
            ->delete(route('api.v1.auth.tokens.delete'))
            ->assertUnauthorized();
    }

    /**
     * Profile info route must return role key
     */
    public function test_info_route_returns_role_in_response(): void
    {
        $userData = ['email' => 'test@example.com', 'password' => 'password'];

        $user = User::factory()->create($userData);
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 536870912]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        // 1. Login and get token
        $loginResponse = $this->actingAsGuest()
                        ->post(route('api.v1.auth.tokens.new'), $userData)
                        ->assertOk()
                        ->assertJson(function (AssertableJson $json) {
                            $json->has('token')
                                ->etc();
                        });

        $token = collect($loginResponse->json())->get('token');

        // 2. Fetch profile info
        $this->get(route('api.v1.auth.info'), [
                'Authorization' => "Bearer {$token}"
            ])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('role')
                    ->etc();
            });
    }

    /**
     * Login route must return plan key in response
     */
    public function test_login_return_important_keys_in_response(): void
    {
        $userData = ['email' => 'test@example.com', 'password' => 'password'];

        $user = User::factory()->create($userData);
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 536870912]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        $this->actingAsGuest()
            ->post(route('api.v1.auth.tokens.new'), $userData)
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('plan')
                    ->has('profile')
                    ->has('role')
                    ->has('token')
                    ->has('used_bytes')
                    ->etc();
            });
    }

    /**
     * User can revoke their current token
     */
    public function test_token_can_be_revoked(): void
    {
        $userData = ['email' => 'test@example.com', 'password' => 'password'];

        $user = User::factory()->create($userData);
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 536870912]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        // 1. Login and get token
        $loginResponse = $this->actingAsGuest()
                        ->post(route('api.v1.auth.tokens.new'), $userData)
                        ->assertOk()
                        ->assertJson(function (AssertableJson $json) {
                            $json->has('token')
                                ->etc();
                        });

        $loginData = collect($loginResponse->json());

        $token = $loginData->get('token');

        // 2. Revoke token
        $this->delete(route('api.v1.auth.tokens.delete'), [], [
                'Authorization' => "Bearer {$token}"
            ])
            ->assertNoContent();
    }

    /**
     * Ensure email uniqueness in users table
     */
    public function test_users_are_unique_by_email(): void
    {
        $userData = ['email' => 'test@example.com', 'password' => 'password'];

        User::factory()->create($userData);

        $this->actingAsGuest()
            ->post(route('api.v1.auth.new'), $userData)
            ->assertUnprocessable();

        $this->assertDatabaseCount(User::class, 1);
    }

    /**
     * Validation works as expected
     */
    public function test_login_validation_working_as_expected(): void
    {
        $data = ['email' => '', 'password' => ''];

        $this->post(route('api.v1.auth.new'), $data)
            ->assertUnprocessable()
            ->assertJson(function (AssertableJson $json) {
                $json->has('errors')
                    ->has('errors.email')
                    ->has('errors.password')
                    ->etc();
            });
    }
}
