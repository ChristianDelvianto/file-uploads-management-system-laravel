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
        // 5GB in bytes; $4.99
        Plan::create(['name' => 'Free', 'price' => 0, 'limit_bytes' => 5368709120]);

        // 20GB in bytes; $9.99
        Plan::create(['name' => 'Pro', 'price' => 999, 'limit_bytes' => 21474836480]);

        // 50GB in bytes; $19.99
        Plan::create(['name' => 'Premium', 'price' => 1999, 'limit_bytes' => 53687091200]);
    }
}
