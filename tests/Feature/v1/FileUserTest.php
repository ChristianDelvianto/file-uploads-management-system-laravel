<?php

namespace Tests\Feature\v1;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FileUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_force_delete_others_file(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user1->id]);

        $this->actingAs($user2, 'sanctum')
            ->delete(route('api.v1.file.destroy', ['file' => $file->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid]);
    }

    public function test_user_cannot_restore_others_trashed_file(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user1->id]);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.file.restore', ['file' => $file->uuid]))
            ->assertForbidden();

        $file->refresh();

        $this->assertTrue($file->trashed());
    }

    public function test_user_cannot_set_others_file_as_trash(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user1->id]);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.file.trash', ['file' => $file->uuid]))
            ->assertForbidden();

        $file->refresh();

        $this->assertFalse($file->trashed());
    }

    public function test_user_cannot_update_others_file_name(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $fileOldName = 'Old name';

        $file = File::factory()->create(['name' => $fileOldName, 'user_id' => $user1->id]);

        $this->actingAs($user2, 'sanctum')
            ->put(route('api.v1.file.update.name', ['file' => $file->uuid]), [
                'name' => 'New file name'
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid, 'name' => $fileOldName]);
    }

    public function test_user_cannot_update_others_file_visibility(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user1->id]);

        $fileVisibility = 'private';

        $this->actingAs($user2, 'sanctum')
            ->put(route('api.v1.file.update.visibility', ['file' => $file->uuid]), [
                'visibility' => 'public'
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid, 'visibility' => $fileVisibility]);
    }

    public function test_user_can_force_delete_their_file(): void
    {
        $fileSize = 1024 * 1024 * 5;

        $user = User::factory()->create(['used_bytes' => $fileSize]);

        $file = File::factory()->deleted()->create(['bytes_size' => $fileSize, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.file.destroy', ['file' => $file->uuid]))
            ->assertOk();

        $this->assertDatabaseMissing(File::class, ['uuid' => $file->uuid]);
    }

    public function test_user_can_restore_their_trashed_file(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->patch(route('api.v1.file.restore', ['file' => $file->uuid]))
            ->assertNoContent();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertFalse($file->trashed());
    }

    public function test_user_can_set_their_file_as_trash(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->patch(route('api.v1.file.trash', ['file' => $file->uuid]))
            ->assertNoContent();

        $file->refresh();

        $this->assertTrue($file->trashed());
    }

    public function test_user_can_update_their_file_name(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['name' => 'Old file name', 'user_id' => $user->id]);

        $fileNewName = 'New file name';

        $this->actingAs($user, 'sanctum')
            ->put(route('api.v1.file.update.name', ['file' => $file->uuid]), [
                'name' => $fileNewName
            ])
            ->assertOk();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid, 'name' => $fileNewName]);
    }

    public function test_user_can_update_their_file_visibility(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $fileNewVisibility = 'public';

        $this->actingAs($user, 'sanctum')
            ->put(route('api.v1.file.update.visibility', ['file' => $file->uuid]), [
                'visibility' => 'public'
            ])
            ->assertOk();

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid, 'visibility' => $fileNewVisibility]);
    }
}
