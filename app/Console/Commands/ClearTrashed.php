<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;

class ClearTrashed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-trashed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete trashed files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fromTimestamp = now()->subDays(config('filesystems.prune_days_gap'));

        File::onlyTrashed()
        ->where('deleted_at', '<=', $fromTimestamp)
        ->orderBy('deleted_at', 'asc') // Start with the oldest
        ->chunkById(50, function ($files) {
            foreach ($files as $file) {
                // 
            }
        });
    }
}
