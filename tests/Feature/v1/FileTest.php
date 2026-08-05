<?php

namespace Tests\Feature\v1;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_delete_file_reduces_user_used_bytes_column(): void
    {
        $userUsedBytes = 1024 * 1024 * 100;
        $user = User::factory()->create(['used_bytes' => $userUsedBytes]);

        $file = File::factory()->deleted()->create(['bytes_size' => $userUsedBytes, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.file.destroy', ['file' => $file->uuid]))
            ->assertOk();

        $this->assertDatabaseHas(User::class, ['id' => $user->id, 'used_bytes' => 0]);

        $this->assertDatabaseMissing(File::class, ['uuid' => $file->uuid]);
    }

    public function test_force_delete_file_removes_file_directory_in_storage(): void
    {
        $disk = Storage::fake('local');
        
        $userUsedBytes = 1024 * 1024 * 100;
        $user = User::factory()->create(['used_bytes' => $userUsedBytes]);

        $file = File::factory()->deleted()->create(['bytes_size' => $userUsedBytes, 'user_id' => $user->id]);
        $filePath = "files/{$file->uuid}";

        $disk->makeDirectory($filePath);

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.file.destroy', ['file' => $file->uuid]))
            ->assertOk();

        $this->assertDatabaseMissing(File::class, ['uuid' => $file->uuid]);

        $this->assertFalse($disk->exists($filePath));
    }

    public function test_force_delete_file_return_used_bytes_in_response(): void
    {
        $userUsedBytes = 1024 * 1024 * 100;
        $user = User::factory()->create(['used_bytes' => $userUsedBytes]);

        $file = File::factory()->deleted()->create(['bytes_size' => $userUsedBytes, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.file.destroy', ['file' => $file->uuid]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('used_bytes')
                    ->etc();
            });

        $this->assertDatabaseEmpty(File::class);
    }

    public function test_private_file_can_only_be_access_only_by_file_owner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->private()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertForbidden();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertForbidden();
    }

    public function test_private_file_download_link_can_be_request_only_by_file_owner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->private()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertForbidden();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertForbidden();
    }

    public function test_public_file_can_be_access_by_everybody(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->public()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertOk();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertOk();
    }

    public function test_public_file_download_link_can_be_request_by_everybody(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->public()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertOk();
    }

    public function test_shared_file_download_link_can_be_request_only_by_authorized_parties(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $file = File::factory()->shared()->create(['user_id' => $user1->id]);

        // Share $file with $user2
        $file->shared()->attach($user2->id);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertForbidden();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // $user2
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // $user3
        $this->actingAs($user3, 'sanctum')
            ->get(route('api.v1.file.link.download', ['file' => $file->uuid]))
            ->assertForbidden();
    }

    public function test_shared_file_can_be_access_only_by_authorized_parties(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $file = File::factory()->shared()->create(['user_id' => $user1->id]);

        // Share $file with $user2
        $file->shared()->attach($user2->id);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertForbidden();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertOk();

        // $user2
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertOk();

        // $user3
        $this->actingAs($user3, 'sanctum')
            ->get(route('api.v1.file.show', ['file' => $file->uuid]))
            ->assertForbidden();
    }
}
