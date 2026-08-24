<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StorageCleaner
{
    /**
     * Models and their columns that reference managed storage paths.
     */
    protected static array $registry = [
        \App\Models\LessonFile::class => ['file_path', 'thumbnail_path'],
        \App\Models\FreeTrialLessonFile::class => ['file_path', 'thumbnail_path'],
        \App\Models\EducationalStage::class => ['thumbnail_path', 'background_image_path'],
        \App\Models\FreeTrialEducationalStage::class => ['thumbnail_path', 'background_image_path'],
        \App\Models\Grade::class => ['thumbnail_path'],
        \App\Models\FreeTrialGrade::class => ['thumbnail_path'],
        \App\Models\Section::class => ['thumbnail_path'],
        \App\Models\Subject::class => ['thumbnail_path'],
        \App\Models\FreeTrialSubject::class => ['thumbnail_path'],
        \App\Models\Unit::class => ['thumbnail_path'],
        \App\Models\Lesson::class => ['thumbnail_path'],
    ];

    /**
     * Known subdirectories inside the thumbnails storage directory.
     */
    protected static array $thumbnailSubdirectories = [
        'stages',
        'grades',
        'sections',
        'subjects',
        'units',
        'lessons',
        'files',
        'free-trial',
        'free_trial_subjects',
        'general',
    ];

    /**
     * Normalize a stored path into canonical representations.
     * Returns null if the path is an external URL, invalid, or outside managed scope.
     */
    public static function normalizePath(?string $path): ?array
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $trimmed = trim($path);

        // Rule 4: Ignore external URLs (http, https, ftp, etc.)
        if (preg_match('#^(https?|ftp)://#i', $trimmed)) {
            return null;
        }

        // Rule 5: Path traversal protection
        if (str_contains($trimmed, '..')) {
            Log::warning("StorageCleaner: Path traversal attempt detected and blocked: {$trimmed}");
            return null;
        }

        // Normalize separators
        $clean = str_replace('\\', '/', $trimmed);
        $clean = ltrim($clean, './');

        // Strip leading 'storage/' if present
        if (str_starts_with($clean, 'storage/')) {
            $clean = substr($clean, 8);
            $clean = ltrim($clean, '/');
        }

        // 1. Check if it belongs to 'lesson_files/'
        if (str_starts_with($clean, 'lesson_files/')) {
            $fullPath = storage_path('app/public/' . $clean);
            return [
                'disk' => 'public',
                'relative_path' => $clean,
                'canonical_relative' => $clean,
                'managed_area' => 'lesson_files',
                'full_path' => $fullPath,
            ];
        }

        // 2. Check if it starts with 'thumbnails/'
        if (str_starts_with($clean, 'thumbnails/')) {
            $relativeUnderThumbnails = substr($clean, 11);
            $fullPath = storage_path('app/public/' . $clean);
            return [
                'disk' => 'thumbnails',
                'relative_path' => $relativeUnderThumbnails,
                'canonical_relative' => $clean,
                'managed_area' => 'thumbnails',
                'full_path' => $fullPath,
            ];
        }

        // 3. Check if it starts with known thumbnail subdirectories directly (e.g. 'stages/xyz.jpg')
        foreach (self::$thumbnailSubdirectories as $subDir) {
            if ($clean === $subDir || str_starts_with($clean, $subDir . '/')) {
                $canonical = 'thumbnails/' . $clean;
                $fullPath = storage_path('app/public/' . $canonical);
                return [
                    'disk' => 'thumbnails',
                    'relative_path' => $clean,
                    'canonical_relative' => $canonical,
                    'managed_area' => 'thumbnails',
                    'full_path' => $fullPath,
                ];
            }
        }

        // Not in a managed area
        return null;
    }

    /**
     * Check whether a given path is referenced by another database record.
     * Soft-deleted records count as references by default to preserve restorable data.
     */
    public static function isPathReferenced(?string $path, ?Model $excludeModel = null, bool $includeSoftDeleted = true): bool
    {
        $norm = self::normalizePath($path);
        if (!$norm) {
            return false;
        }

        $targetCanonical = $norm['canonical_relative'];

        try {
            foreach (self::$registry as $modelClass => $columns) {
                if (!class_exists($modelClass)) {
                    continue;
                }

                $query = $modelClass::query();

                // Include soft-deleted records if requested and supported
                if ($includeSoftDeleted && in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
                    $query->withTrashed();
                }

                // Exclude the current model being deleted/updated if specified
                if ($excludeModel !== null && get_class($excludeModel) === $modelClass && $excludeModel->exists) {
                    $query->where($excludeModel->getKeyName(), '!=', $excludeModel->getKey());
                }

                // Query records where at least one of the columns is not null
                $query->where(function ($q) use ($columns) {
                    foreach ($columns as $index => $col) {
                        if ($index === 0) {
                            $q->whereNotNull($col);
                        } else {
                            $q->orWhereNotNull($col);
                        }
                    }
                });

                // Select only key and relevant columns to minimize memory/query overhead
                $selectCols = array_merge([$excludeModel ? $excludeModel->getKeyName() : 'id'], $columns);
                $records = $query->select($selectCols)->get();

                foreach ($records as $record) {
                    foreach ($columns as $col) {
                        $val = $record->$col;
                        if (!$val) {
                            continue;
                        }
                        $recordNorm = self::normalizePath($val);
                        if ($recordNorm && $recordNorm['canonical_relative'] === $targetCanonical) {
                            return true;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("StorageCleaner: Database reference check failed: " . $e->getMessage());
            // In case of database error, return true defensively so we NEVER delete files accidentally
            return true;
        }

        return false;
    }

    /**
     * Safely delete a main file from storage if unreferenced, local, and within managed scope.
     */
    public static function deleteFile(?string $path, ?Model $excludeModel = null): bool
    {
        if (!$path) {
            return false;
        }

        $norm = self::normalizePath($path);
        if (!$norm || $norm['managed_area'] !== 'lesson_files') {
            return false;
        }

        // Rule 3: Never delete shared files
        if (self::isPathReferenced($path, $excludeModel, true)) {
            Log::info("StorageCleaner: Preserving shared file '{$path}' referenced by other records.");
            return false;
        }

        if (File::exists($norm['full_path'])) {
            try {
                return File::delete($norm['full_path']);
            } catch (\Throwable $e) {
                Log::error("StorageCleaner: Error deleting file '{$norm['full_path']}': " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Safely delete a thumbnail or background image from storage if unreferenced, local, and within managed scope.
     */
    public static function deleteThumbnail(?string $path, ?Model $excludeModel = null): bool
    {
        if (!$path) {
            return false;
        }

        $norm = self::normalizePath($path);
        if (!$norm || $norm['managed_area'] !== 'thumbnails') {
            return false;
        }

        // Rule 3: Never delete shared files
        if (self::isPathReferenced($path, $excludeModel, true)) {
            Log::info("StorageCleaner: Preserving shared thumbnail '{$path}' referenced by other records.");
            return false;
        }

        if (File::exists($norm['full_path'])) {
            try {
                return File::delete($norm['full_path']);
            } catch (\Throwable $e) {
                Log::error("StorageCleaner: Error deleting thumbnail '{$norm['full_path']}': " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Scan managed directories and delete unreferenced orphan files.
     */
    public static function cleanOrphanedFiles(bool $dryRun = false, bool $includeSoftDeleted = true): array
    {
        $managedRoots = [
            storage_path('app/public/lesson_files'),
            storage_path('app/public/thumbnails'),
        ];

        $scannedCount = 0;
        $referencedCount = 0;
        $orphans = [];
        $totalBytes = 0;

        // Test database connectivity before scanning
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            Log::error("StorageCleaner: Cannot clean orphaned files because DB is unreachable: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed. Storage cleanup aborted to protect files: " . $e->getMessage());
        }

        foreach ($managedRoots as $root) {
            if (!File::isDirectory($root)) {
                continue;
            }

            $allFiles = File::allFiles($root);

            foreach ($allFiles as $file) {
                $filename = $file->getFilename();

                // Skip system/git files
                if ($filename === '.gitignore' || $filename === '.DS_Store' || str_starts_with($filename, '.')) {
                    continue;
                }

                $scannedCount++;
                $fullPath = $file->getRealPath();
                $publicRoot = storage_path('app/public');
                $relative = str_replace('\\', '/', substr($fullPath, strlen($publicRoot) + 1));

                if (self::isPathReferenced($relative, null, $includeSoftDeleted)) {
                    $referencedCount++;
                } else {
                    $size = $file->getSize();
                    $orphans[] = [
                        'path' => $relative,
                        'full_path' => $fullPath,
                        'size' => $size,
                    ];
                    $totalBytes += $size;

                    if (!$dryRun) {
                        try {
                            File::delete($fullPath);
                        } catch (\Throwable $e) {
                            Log::error("StorageCleaner: Failed deleting orphan file '{$fullPath}': " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return [
            'scanned_count' => $scannedCount,
            'referenced_count' => $referencedCount,
            'orphan_count' => count($orphans),
            'orphans' => $orphans,
            'freed_bytes' => $totalBytes,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Safely reset managed storage directories, wiping uploaded files while preserving directory structure.
     */
    public static function resetManagedStorage(): array
    {
        $managedRoots = [
            storage_path('app/public/lesson_files'),
            storage_path('app/public/thumbnails'),
        ];

        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($managedRoots as $root) {
            if (!File::isDirectory($root)) {
                continue;
            }

            $allFiles = File::allFiles($root);

            foreach ($allFiles as $file) {
                $filename = $file->getFilename();
                if ($filename === '.gitignore' || $filename === '.DS_Store' || str_starts_with($filename, '.')) {
                    continue;
                }

                $size = $file->getSize();
                $fullPath = $file->getRealPath();

                try {
                    if (File::delete($fullPath)) {
                        $deletedCount++;
                        $freedBytes += $size;
                    }
                } catch (\Throwable $e) {
                    Log::error("StorageCleaner: Failed deleting file during reset '{$fullPath}': " . $e->getMessage());
                }
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'freed_bytes' => $freedBytes,
        ];
    }
}
