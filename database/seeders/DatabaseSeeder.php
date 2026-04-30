<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $disk = config('filesystems.default');

        // Clear all previous directories and files
        Storage::disk($disk)->deleteDirectory('files');
        Storage::disk($disk)->deleteDirectory('uploads');

        // Recreate the necessary directories
        Storage::disk($disk)->makeDirectory('files');
        Storage::disk($disk)->makeDirectory('uploads');

        $this->call([
            PlanSeeder::class,
            UserSeeder::class
        ]);
    }
}
