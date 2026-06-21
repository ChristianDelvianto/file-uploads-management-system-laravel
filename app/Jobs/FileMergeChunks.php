<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Upload;
use App\Services\v1\StorageService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FileMergeChunks implements ShouldQueue
{
    use Queueable;

    public StorageService $storageService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Upload $upload,
        public string $fileName
    ) {
        $this->storageService = app(StorageService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->storageService->mergeChunks($this->upload, $this->fileName);

            File::where('uuid', $this->upload->uuid)->update(['status' => 'completed']);
        } catch (Exception $e) {
            report($e);

            File::where('uuid', $this->upload->uuid)->update(['status' => 'failed']);

            // Create record for admin statistic
            // Email / broadcast to user channel

            $this->fail($e);
        }
    }
}
