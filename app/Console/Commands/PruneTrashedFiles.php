<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;

class PruneTrashedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-trashed-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timestamp = now()->subDays(config('filesystems.prune_days_gap'));

        File::onlyTrashed()
        ->where('deleted_at', '<', $timestamp)
        ->orderBy('deleted_at', 'asc') // Start with the oldest
        ->chunkById(50, function ($files) {
            foreach ($files as $file) {
                // 
            }
        });
    }
}
