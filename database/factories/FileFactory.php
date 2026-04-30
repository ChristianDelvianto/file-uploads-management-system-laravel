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

        $duration = $category === 'video' || $category === 'audio'
                    ? fake()->numberBetween(30, 3600)
                    : null;

        $thumbnailName = $category === 'video'
                        ? 'file_thumbnail.jpeg'
                        : null;

        return [
            'uuid' => fake()->uuid(),
            'status' => 'completed', // 'failed', 'processing', 'completed'
            'visibility' => 'private', // 'private', 'public', 'shared'
            'is_scanned' => false,
            'disk' => fake()->randomElement(['azure', 'gdrive', 'local', 'r2', 'supabase', 's3']),
            'category' => $category,
            'extension' => $extension,
            'mime_type' => $mime,
            'name' => fake()->words(6, true),
            'duration' => $duration,
            'bytes_size' => fake()->numberBetween(5 * 1024),
            'storage_name' => 'somewhere_in_the_storage.' . $extension,
            'thumbnail_name' => $thumbnailName
        ];
    }

    /**
     * Indicate that the model should be deleted
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now()
        ]);
    }
}
