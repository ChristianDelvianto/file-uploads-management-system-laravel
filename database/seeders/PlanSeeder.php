<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Free
        Plan::create([
            'name' => 'Free',
            'price_cents' => 0,
            'limit_bytes' => 2147483648 // 2GB in bytes
        ]);

        // Student
        Plan::create([
            'name' => 'Student',
            'price_cents' => 499, // $4.99
            'limit_bytes' => 5368709120 // 5GB in bytes
        ]);

        // Pro
        Plan::create([
            'name' => 'Pro',
            'price_cents' => 999, // $9.99
            'limit_bytes' => 21474836480 // 20GB in bytes
        ]);

        // Premium
        Plan::create([
            'name' => 'Premium',
            'price_cents' => 1999, // $19.99
            'limit_bytes' => 53687091200 // 50GB in bytes
        ]);
    }
}
