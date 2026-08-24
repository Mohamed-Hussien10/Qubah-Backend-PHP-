<?php

namespace App\Console\Commands;

use App\Services\StorageCleaner;
use Illuminate\Console\Command;

class StorageCleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clean
                            {--dry-run : Report unreferenced files without deleting them}
                            {--soft-deleted : Treat files belonging only to soft-deleted records as unreferenced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan managed storage directories and clean unreferenced orphan files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        // By default, soft-deleted records protect their files (includeSoftDeleted = true).
        // If --soft-deleted flag is passed, includeSoftDeleted is false.
        $includeSoftDeleted = !$this->option('soft-deleted');

        $this->info($dryRun ? 'Scanning managed storage directories (DRY RUN)...' : 'Cleaning unreferenced files from managed storage...');

        $result = StorageCleaner::cleanOrphanedFiles($dryRun, $includeSoftDeleted);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Scanned Files', $result['scanned_count']],
                ['Referenced Files', $result['referenced_count']],
                ['Orphaned Files Found', $result['orphan_count']],
                ['Total Orphan Size', round($result['freed_bytes'] / 1024, 2) . ' KB (' . round($result['freed_bytes'] / (1024 * 1024), 2) . ' MB)'],
                ['Execution Mode', $dryRun ? 'DRY RUN (No files deleted)' : 'DELETED'],
            ]
        );

        if (!empty($result['orphans'])) {
            $this->newLine();
            $this->info('Orphaned Files List:');
            $rows = [];
            foreach (array_slice($result['orphans'], 0, 50) as $orphan) {
                $rows[] = [$orphan['path'], round($orphan['size'] / 1024, 2) . ' KB'];
            }
            $this->table(['Relative Path', 'Size'], $rows);

            if (count($result['orphans']) > 50) {
                $this->comment('... and ' . (count($result['orphans']) - 50) . ' more files.');
            }
        }

        if ($dryRun) {
            $this->comment('Dry run complete. No files were deleted.');
        } else {
            $this->info("Cleanup complete. Removed {$result['orphan_count']} unreferenced files.");
        }

        return self::SUCCESS;
    }
}
