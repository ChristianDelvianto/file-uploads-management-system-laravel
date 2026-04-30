<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::factory()->create(['role' => 'admin', 'name' => 'Admin', 'email' => 'admin@example.com']);

        // Get Free Plan
        $freePlan = Plan::firstWhere('price_cents', 0);

        if (!$freePlan) {
            $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 5368709120]); // 5 GB
        }

        // Test User
        $user1 = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        PlanUser::create(['plan_id' => $freePlan->id, 'user_id' => $user1->id]);

        // Test User 2
        $user2 = User::factory()->create(['name' => 'My self', 'email' => 'myself@example.com']);
        PlanUser::create(['plan_id' => $freePlan->id, 'user_id' => $user2->id]);

        // Generate 10 random users with Free plan
        for ($i = 1; $i < 10; $i++) {
            $user = User::factory()->create();
            PlanUser::create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);
        }
    }
}
