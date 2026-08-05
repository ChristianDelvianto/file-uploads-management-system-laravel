<?php

namespace Tests\Feature\v1;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FileGuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_force_delete_file(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->delete(route('api.v1.file.destroy', ['file' => $file->uuid]))
            ->assertUnauthorized();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid]);
    }

    public function test_guest_cannot_restore_trashed_file(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->patch(route('api.v1.file.restore', ['file' => $file->uuid]))
            ->assertUnauthorized();

        $file->refresh();

        $this->assertTrue($file->trashed());
    }

    public function test_guest_cannot_set_file_as_trash(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->patch(route('api.v1.file.trash', ['file' => $file->uuid]))
            ->assertUnauthorized();

        $file->refresh();

        $this->assertFalse($file->trashed());
    }

    public function test_guest_cannot_update_file_name(): void
    {
        $user = User::factory()->create();

        $fileOldName = 'Old name';

        $file = File::factory()->create(['name' => $fileOldName, 'user_id' => $user->id]);

        $this->actingAsGuest()
            ->put(route('api.v1.file.update.name', ['file' => $file->uuid]), [
                'name' => 'New file name'
            ])
            ->assertUnauthorized();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid, 'name' => $fileOldName]);
    }

    public function test_guest_cannot_update_file_visibility(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->put(route('api.v1.file.update.visibility', ['file' => $file->uuid]), [
                'visibility' => 'public'
            ])
            ->assertUnauthorized();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid, 'visibility' => 'private']);
    }
}
