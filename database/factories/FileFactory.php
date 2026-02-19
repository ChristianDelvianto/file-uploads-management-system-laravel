<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mime = fake()->randomElement(['image/jpg', 'image/jpeg', 'image/webp', 'video/mp4', 'video/webm', 'audio/mp3']);

        $category = explode('/', $mime)[0];
        $extension = explode('/', $mime)[1];

        return [
            'category' => $category,
            'mime_type' => $mime,
            'name' => fake()->words(6, true) . ".{$extension}",
            'size' => fake()->numberBetween(5 * 1024),
            'thumbnail_path' => null,
            'storage_path' => 'somewhere_to_put',
            'disk' => fake()->randomElement(['s3', 'local', 'r2', 'gdrive']),
        ];
    }

    /**
     * Indicate that the model should be deleted
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
