<?php

namespace Tests\Feature\v1;

use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guest must not be able to store a new upload record.
     */
    // public function test_guest_cannot_create_upload(): void
    // {
    //     // GD extension required for creating fake image files
    //     $thumbnailSize = 1024 * 1024 * 1;
    //     $thumbnailFile = UploadedFile::fake()->image('thumbnail.jpg')->size($thumbnailSize);

    //     $this->actingAsGuest()
    //         ->post(route('api.v1.uploads.store'), [
    //             'type' => 'mp4',
    //             'name' => 'A test video file',
    //             'mime_type' => 'video/avi',
    //             'chunk_count' => 1,
    //             'bytes_size' => 1024 * 1024 * 6, // 6 MB
    //             'duration' => 24,
    //             'thumbnail' => $thumbnailFile
    //         ])
    //         ->assertUnauthorized();

    //     $this->assertDatabaseEmpty(Upload::class);
    // }

    /**
     * Guest must not be able to cancel an upload.
     */
    public function test_guest_cannot_cancel_upload(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAsGuest()
            ->patch(route('api.v1.uploads.cancel', ['upload' => $upload->uuid]))
            ->assertUnauthorized();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * Guest must not be able to complete an upload.
     */
    public function test_guest_cannot_complete_upload(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAsGuest()
            ->patch(route('api.v1.uploads.complete', ['upload' => $upload->uuid]))
            ->assertUnauthorized();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * Guest must not be able to fetch an upload.
     */
    public function test_guest_cannot_fetch_upload(): void
    {
        $user = User::factory()->create();
        
        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAsGuest()
            ->get(route('api.v1.uploads.show', ['upload' => $upload->uuid]))
            ->assertUnauthorized();
    }

    /**
     * Guest must not be able to upload chunks.
     */
    public function test_guest_cannot_upload_chunks(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'last_chunk_index' => null, 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $chunkFile = UploadedFile::fake()
                    ->create('chunk', 1024 * 1024 * 10); // 10 MB

        $this->actingAsGuest()
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => 0,
                'chunk' => $chunkFile
            ])
            ->assertUnauthorized();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * Users cannot cancel other users' uploads.
     */
    public function test_user_cannot_cancel_other_user_upload(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user1->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.uploads.cancel', ['upload' => $upload->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * Users cannot complete other users' uploads.
     */
    public function test_user_cannot_complete_other_user_upload(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user1->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.uploads.complete', ['upload' => $upload->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * 
     */
    public function test_user_cannot_complete_upload_when_not_in_started_status(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'canceled', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user, 'sanctum')
            ->patch(route('api.v1.uploads.complete', ['upload' => $upload->uuid]))
            ->assertBadRequest();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * 
     */
    public function test_user_cannot_complete_upload_when_used_bytes_exceed_plan_limit(): void
    {
        // 
    }

    /**
     * 
     */
    public function test_user_cannot_create_upload_exceed_plan_limit(): void
    {
        // 
    }

    /**
     * 
     */
    public function test_user_cannot_fetch_other_user_upload(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user1->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user2, 'sanctum')
            ->get(route('api.v1.uploads.show', ['upload' => $upload->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * 
     */
    public function test_user_cannot_upload_chunks_to_other_user_upload(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user1->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user2, 'sanctum')
            ->patch(route('api.v1.uploads.complete', ['upload' => $upload->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * 
     */
    public function test_user_can_cancel_their_upload(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user, 'sanctum')
            ->patch(route('api.v1.uploads.cancel', ['upload' => $upload->uuid]))
            ->assertNoContent();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            'status' => 'canceled'
        ]);
    }

    /**
     * 
     */
    public function test_user_can_complete_their_upload(): void
    {
        // 
    }

    /**
     * 
     */
    public function test_user_can_fetch_their_upload(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $this->actingAs($user, 'sanctum')
            ->get(route('api.v1.uploads.show', ['upload' => $upload->uuid]))
            ->assertOk();
    }

    /**
     * 
     */
    public function test_user_can_upload_chunks_to_their_upload(): void
    {
        $disk = Storage::fake('local');

        // 
    }

    /**
     * Chunk index route parameter must be an integer.
     */
    public function test_chunk_index_must_be_an_integer(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $chunkSize = 1024 * 1024 * 10;
        $chunkFile = UploadedFile::fake()->create('chunk', $chunkSize);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => 'not-an-integer',
                'chunk' => $chunkFile
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['index']);

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'used_bytes' => 0
        ]);
    }

    /**
     * Chunk index route parameter must be non-negative.
     */
    public function test_chunk_index_must_be_non_negative(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $chunkSize = 1024 * 1024 * 10;
        $chunkFile = UploadedFile::fake()->create('chunk', $chunkSize);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => -1,
                'chunk' => $chunkFile
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['index']);
    }

    /**
     * Chunk index route parameter cannot exceed total chunks.
     */
    public function test_chunk_index_cannot_exceed_or_equal_total_chunks(): void
    {
        $user = User::factory()->create();

        $uploadData = ['status' => 'started', 'chunk_count' => 5, 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $chunkSize = 1024 * 1024 * 10;
        $chunkFile = UploadedFile::fake()->create('chunk', $chunkSize);

        // Equal to chunk count
        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => 5,
                'chunk' => $chunkFile
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['index']);

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);

        // Greater than chunk count
        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => 6,
                'chunk' => $chunkFile
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['index']);

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            ...$uploadData
        ]);
    }

    /**
     * 
     */
    public function test_chunk_upload_stores_chunk_file(): void
    {
        $chunkMaxSize = config('filesystems.max_chunk_size_per_request');
        $disk = Storage::fake('local');

        $user = User::factory()->create();
        $uploadData = ['status' => 'started', 'chunk_count' => 5, 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $disk->makeDirectory("uploads/{$upload->uuid}");
        $disk->makeDirectory("uploads/{$upload->uuid}/chunks");

        $this->assertTrue($disk->exists("uploads/{$upload->uuid}"));
        $this->assertTrue($disk->exists("uploads/{$upload->uuid}/chunks"));

        $chunkIndex = 0;
        $chunkFile = UploadedFile::fake()->create('chunk', $chunkMaxSize);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => $chunkIndex,
                'chunk' => $chunkFile
            ])
            ->assertNoContent();

        $chunkName = "_part_{$chunkIndex}.{$upload->extension}";
        $chunkPath = "uploads/{$upload->uuid}/chunks";

        $this->assertTrue($disk->exists("{$chunkPath}/{$chunkName}"));
    }

    /**
     * 
     */
    public function test_chunk_upload_updates_last_chunk_index_column(): void
    {
        $chunkMaxSize = config('filesystems.max_chunk_size_per_request');
        $disk = Storage::fake('local');

        $user = User::factory()->create();
        $uploadData = ['status' => 'started', 'chunk_count' => 5, 'user_id' => $user->id];
        $upload = Upload::factory()->create($uploadData);

        $disk->makeDirectory("uploads/{$upload->uuid}");
        $disk->makeDirectory("uploads/{$upload->uuid}/chunks");

        $this->assertTrue($disk->exists("uploads/{$upload->uuid}"));
        $this->assertTrue($disk->exists("uploads/{$upload->uuid}/chunks"));

        $chunkIndex = 0;
        $chunkFile = UploadedFile::fake()->create('chunk', $chunkMaxSize);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store.chunks', ['upload' => $upload->uuid]), [
                'index' => $chunkIndex,
                'chunk' => $chunkFile
            ])
            ->assertNoContent();

        $this->assertDatabaseHas(Upload::class, [
            'uuid' => $upload->uuid,
            'last_chunk_index' => 0
        ]);
    }

    /**
     * Upload outside the merge threshold must NOT dispatch a merge job.
     */
    public function test_upload_completion_outside_merge_threshold_must_not_dispatch_merge_job(): void
    {
        // 
    }

    /**
     * Upload within the merge threshold should dispatch a merge job.
     */
    public function test_upload_completion_within_merge_threshold_dispatch_merge_job(): void
    {
        Queue::fake();

        // 
    }

    /**
     * Upload for audio files require a duration.
     */
    public function test_upload_for_audio_requires_duration(): void
    {
        $user = User::factory()->create();

        /**
         * Attach user to free plan
         * Otherwise UploadStoreRequest will throws error
         */
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 536870912]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store'), [
                'type' => 'mp3',
                'name' => 'A test audio file',
                'mime_type' => 'audio/mp3',
                'chunk_count' => 1,
                'bytes_size' => 1024 * 1024 * 6, // 6 MB
                // 'duration' => 134,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration']);

        $this->assertDatabaseEmpty(Upload::class);

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'used_bytes' => 0
        ]);
    }

    /**
     * Upload for video files require a duration.
     */
    public function test_upload_for_video_requires_duration(): void
    {
        $user = User::factory()->create();

        /**
         * Attach user to free plan
         * Otherwise UploadStoreRequest will throws error
         */
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 536870912]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store'), [
                'type' => 'mp4',
                'name' => 'A test video file',
                'mime_type' => 'video/mp4',
                'chunk_count' => 1,
                'bytes_size' => 1024 * 1024 * 9, // 9 MB
                'duration' => ''
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration']);

        $this->assertDatabaseEmpty(Upload::class);

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'used_bytes' => 0
        ]);
    }

    /**
     * Upload for video files require a thumbnail.
     */
    public function test_upload_for_video_requires_thumbnail(): void
    {
        $user = User::factory()->create();

        /**
         * Attach user to free plan
         * Otherwise UploadStoreRequest will throws error
         */
        $freePlan = Plan::factory()->create(['name' => 'Free', 'price_cents' => 0, 'limit_bytes' => 536870912]);
        PlanUser::factory()->create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->post(route('api.v1.uploads.store'), [
                'type' => 'mp4',
                'name' => 'A test video file',
                'mime_type' => 'video/mp4',
                'chunk_count' => 1,
                'bytes_size' => 1024 * 1024 * 6, // 6 MB
                'duration' => 134,
                'thumbnail' => ''
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['thumbnail']);

        $this->assertDatabaseEmpty(Upload::class);

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'used_bytes' => 0
        ]);
    }
}
