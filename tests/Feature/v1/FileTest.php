<?php

namespace Tests\Feature\v1;

use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensures admin could not delete users' trashed files.
     */
    public function test_admin_cannot_force_delete_users_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin, 'sanctum')
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertForbidden();
    }

    /**
     * Ensure admin could not restore user's trashed files.
     */
    public function test_admin_cannot_restore_users_trashed_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $user = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user->id]);

        $this->actingAs($admin, 'sanctum')
            ->patch(route('api.v1.files.restore', ['file' => $file->uuid]))
            ->assertForbidden();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertTrue($file->trashed());
    }

    /**
     * Ensures admin could not set user's file as trash
     */
    public function test_admin_cannot_set_users_file_as_trash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin, 'sanctum')
            ->patch(route('api.v1.files.trash', ['file' => $file->uuid]))
            ->assertForbidden();
    }

    /**
     * Ensures admin could not update user's file name.
     */
    public function test_admin_cannot_update_users_file_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $user = User::factory()->create();

        $fileOldName = 'Old name';

        $file = File::factory()->create(['name' => $fileOldName, 'user_id' => $user->id]);

        $fileNewName = 'New file name';

        $this->actingAs($admin, 'sanctum')
            ->put(route('api.v1.files.update.name', ['file' => $file->uuid]), [
                'name' => $fileNewName
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'name' => $fileOldName
        ]);
    }

    /**
     * Ensures admin could not update users' file visibility
     */
    public function test_admin_cannot_update_users_file_visibility(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $fileNewVisibility = 'public';

        $this->actingAs($admin, 'sanctum')
            ->put(route('api.v1.files.update.visibility', ['file' => $file->uuid]), [
                'visibility' => $fileNewVisibility
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'visibility' => 'private'
        ]);
    }

    /**
     * Ensures guest could not delete users' trashed files.
     */
    public function test_guest_cannot_force_delete_users_file(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertUnauthorized();
    }

    /**
     * * Ensures guest could not set user's file as trash
     */
    public function test_guest_cannot_restore_users_trashed_file(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->patch(route('api.v1.files.restore', ['file' => $file->uuid]))
            ->assertUnauthorized();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertTrue($file->trashed());
    }

    /**
     * Ensures guest could not set user's file as trash
     */
    public function test_guest_cannot_set_file_as_trash(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->actingAsGuest()
            ->patch(route('api.v1.files.trash', ['file' => $file->uuid]))
            ->assertUnauthorized();
    }

    /**
     * Ensures admin could not update user's file name.
     */
    public function test_guest_cannot_update_file_name(): void
    {
        $user = User::factory()->create();

        $fileOldName = 'Old name';

        $file = File::factory()->create(['name' => $fileOldName, 'user_id' => $user->id]);

        $fileNewName = 'New file name';

        $this->actingAsGuest()
            ->put(route('api.v1.files.update.name', ['file' => $file->uuid]), [
                'name' => $fileNewName
            ])
            ->assertUnauthorized();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'name' => $fileOldName
        ]);
    }

    /**
     * * Ensures admin could not update user's file visibility.
     */
    public function test_guest_cannot_update_file_visibility(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $fileNewVisibility = 'public';

        $this->actingAsGuest()
            ->put(route('api.v1.files.update.visibility', ['file' => $file->uuid]), [
                'visibility' => $fileNewVisibility
            ])
            ->assertUnauthorized();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'visibility' => 'private'
        ]);
    }

    /**
     * * Ensures admin could not update user's file name.
     */
    public function test_user_cannot_force_delete_others_file(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user1->id]);

        $this->assertTrue($file->trashed());

        $this->actingAs($user2, 'sanctum')
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertForbidden();

        $this->assertDatabaseCount(File::class, 1);

        $this->assertDatabaseHas(File::class, ['uuid' => $file->uuid]);
    }

    /**
     * Ensures user could not restore other users trashed files
     */
    public function test_user_cannot_restore_others_trashed_file(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user1->id]);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.files.restore', ['file' => $file->uuid]))
            ->assertForbidden();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertTrue($file->trashed());
    }

    /**
     * Ensures user could not set other users files as trash
     */
    public function test_user_cannot_set_others_file_as_trash(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user1->id]);

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'deleted_at' => null
        ]);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.files.trash', ['file' => $file->uuid]))
            ->assertForbidden();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertTrue(!$file->trashed());
    }

    /**
     * Ensures user could not update other users files name
     */
    public function test_user_cannot_update_others_file_name(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $fileOldName = 'Old name';

        $file = File::factory()->create(['name' => $fileOldName, 'user_id' => $user1->id]);

        $fileNewName = 'New file name';

        $this->actingAs($user2, 'sanctum')
            ->put(route('api.v1.files.update.name', ['file' => $file->uuid]), [
                'name' => $fileNewName
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'name' => $fileOldName
        ]);
    }

    /**
     * Ensures user could not update other users files visibility
     */
    public function test_user_cannot_update_others_file_visibility(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user1->id]);

        $fileNewVisibility = 'public';

        $this->actingAs($user2, 'sanctum')
            ->put(route('api.v1.files.update.visibility', ['file' => $file->uuid]), [
                'visibility' => $fileNewVisibility
            ])
            ->assertForbidden();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'visibility' => 'private'
        ]);
    }

    /**
     * Ensures user could delete their trashed file
     */
    public function test_user_can_force_delete_their_file(): void
    {
        $fileSize = 1024 * 1024 * 5;

        $user = User::factory()->create(['used_bytes' => $fileSize]);

        $file = File::factory()->deleted()->create(['bytes_size' => $fileSize, 'user_id' => $user->id]);

        $this->assertTrue($file->trashed());

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertOk();

        $this->assertDatabaseEmpty(File::class);
    }

    /**
     * Ensures user could restore their trashed file
     */
    public function test_user_can_restore_their_trashed_file(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->deleted()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->patch(route('api.v1.files.restore', ['file' => $file->uuid]))
            ->assertNoContent();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertTrue(!$file->trashed());
    }

    /**
     * Ensures user could set their file as trash
     */
    public function test_user_can_set_their_file_as_trash(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'deleted_at' => null
        ]);

        $this->actingAs($user, 'sanctum')
            ->patch(route('api.v1.files.trash', ['file' => $file->uuid]))
            ->assertNoContent();

        $file = File::withTrashed()->firstWhere('uuid', '=', $file->uuid);

        $this->assertTrue($file->trashed());
    }

    /**
     * Ensures user could update their files name
     */
    public function test_user_can_update_their_file_name(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['name' => 'Old file name', 'user_id' => $user->id]);

        $fileNewName = 'New file name';

        $this->actingAs($user, 'sanctum')
            ->put(route('api.v1.files.update.name', ['file' => $file->uuid]), [
                'name' => $fileNewName
            ])
            ->assertOk();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'name' => $fileNewName
        ]);
    }

    /**
     * Ensures user could update their files visibility
     */
    public function test_user_can_update_their_file_visibility(): void
    {
        $user = User::factory()->create();

        $file = File::factory()->create(['user_id' => $user->id]);

        $fileNewVisibility = 'public';

        $this->actingAs($user, 'sanctum')
            ->put(route('api.v1.files.update.visibility', ['file' => $file->uuid]), [
                'visibility' => $fileNewVisibility
            ])
            ->assertOk();

        $this->assertDatabaseHas(File::class, [
            'uuid' => $file->uuid,
            'visibility' => $fileNewVisibility
        ]);
    }

    /**
     * Ensures deleting trash file reduce user's used_bytes
     */
    public function test_force_delete_file_reduces_user_used_bytes_column(): void
    {
        $userUsedBytes = 1024 * 1024 * 100;
        $user = User::factory()->create(['used_bytes' => $userUsedBytes]);

        $file = File::factory()->deleted()->create(['bytes_size' => $userUsedBytes, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertOk();

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'used_bytes' => 0
        ]);

        $this->assertDatabaseMissing(File::class, ['uuid' => $file->uuid]);

        $this->assertDatabaseEmpty(File::class);
    }

    /**
     * Ensures deleting trash file remove file directory in storage
     */
    public function test_force_delete_file_removes_file_directory_in_storage(): void
    {
        $disk = Storage::fake('local');
        
        $userUsedBytes = 1024 * 1024 * 100;
        $user = User::factory()->create(['used_bytes' => $userUsedBytes]);

        $file = File::factory()->deleted()->create(['bytes_size' => $userUsedBytes, 'user_id' => $user->id]);

        $disk->makeDirectory("files/{$file->uuid}");

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertOk();

        $this->assertDatabaseEmpty(File::class);

        $this->assertTrue(!$disk->exists("files/{$file->uuid}"));
    }

    /**
     * Ensures deleting trash file return user's used_bytes in response
     */
    public function test_force_delete_file_return_used_bytes_in_response(): void
    {
        $userUsedBytes = 1024 * 1024 * 100;
        $user = User::factory()->create(['used_bytes' => $userUsedBytes]);

        $file = File::factory()->deleted()->create(['bytes_size' => $userUsedBytes, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->delete(route('api.v1.files.destroy', ['file' => $file->uuid]))
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('used_bytes')
                    ->etc();
            });

        $this->assertDatabaseEmpty(File::class);
    }

    /**
     * Ensures private file could only be access by file owner
     */
    public function test_private_file_can_only_be_access_only_by_file_owner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->private()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertNotFound();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertNotFound();
    }

    /**
     * Ensures private file download link could only be request by file owner
     */
    public function test_private_file_download_link_can_be_request_only_by_file_owner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->private()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertNotFound();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertNotFound();
    }

    /**
     * 
     */
    public function test_public_file_can_be_access_by_everybody(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->public()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertOk();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertOk();
    }

    /**
     * 
     */
    public function test_public_file_download_link_can_be_request_by_everybody(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $file = File::factory()->public()->create(['user_id' => $user1->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // Other user
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertOk();
    }

    /**
     * 
     */
    public function test_shared_file_download_link_can_be_request_only_by_authorized_parties(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $file = File::factory()->private()->create(['user_id' => $user1->id]);

        // Share $file with $user2
        FileShare::factory()->create(['file_id' => $file->id, 'user_id' => $user2->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertForbidden();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // $user2
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertOk();

        // $user3
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.link.download', ['file' => $file->uuid]))
            ->assertForbidden();
    }

    /**
     * 
     */
    public function test_shared_file_can_be_access_only_by_authorized_parties(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $file = File::factory()->shared()->create(['user_id' => $user1->id]);

        // Share $file with $user2
        FileShare::factory()->create(['file_id' => $file->id, 'user_id' => $user2->id]);

        // Guest
        $this->actingAsGuest()
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertNotFound();

        // File owner
        $this->actingAs($user1, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertOk();

        // $user2
        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertOk();

        // $user3
        $this->actingAs($user3, 'sanctum')
            ->get(route('api.v1.files.show', ['file' => $file->uuid]))
            ->assertNotFound();
    }
}
