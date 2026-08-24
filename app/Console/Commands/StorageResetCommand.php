<?php

namespace App\Console\Commands;

use App\Services\StorageCleaner;
use Illuminate\Console\Command;

class StorageResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:reset {--force : Confirm storage wipe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely wipe all uploaded files from managed storage directories while preserving directory structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->error('===========================================================');
            $this->error(' DANGER: This command will wipe all managed uploaded files! ');
            $this->error(' You must provide the --force flag to execute this action. ');
            $this->error(' Usage: php artisan storage:reset --force                  ');
            $this->error('===========================================================');
            return self::FAILURE;
        }

        $this->warn('Resetting managed storage directories (storage/app/public/lesson_files, storage/app/public/thumbnails)...');

        $result = StorageCleaner::resetManagedStorage();

        $this->info("Storage reset complete! Removed {$result['deleted_count']} files (" . round($result['freed_bytes'] / 1024, 2) . " KB freed).");
        $this->info('Directory hierarchy and placeholder files preserved.');

        return self::SUCCESS;
    }
}
