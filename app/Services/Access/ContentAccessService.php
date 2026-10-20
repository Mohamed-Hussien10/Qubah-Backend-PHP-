<?php

namespace App\Services\Access;

use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\FreeTrialLessonFile;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class ContentAccessService
{
    /**
     * Generate a tamper-proof signed URL for a file that cleanly ends with its filename and extension.
     * Format: /api/v1/files/s/{signature}/{expires}/{path/to/file.zip}
     */
    public static function generateSecureFileUrl(string $filePath, int $validDays = 7): string
    {
        $cleanPath = ltrim(preg_replace('/^storage\//', '', $filePath), '/');
        $expires = now()->addDays($validDays)->timestamp;
        $appKey = config('app.key');

        $dataToSign = "{$expires}|{$cleanPath}";
        $signature = hash_hmac('sha256', $dataToSign, $appKey);

        return url("api/v1/files/s/{$signature}/{$expires}/{$cleanPath}");
    }

    /**
     * Validate a path-based signature.
     */
    public static function validateSecureFilePath(string $signature, int|string $expires, string $path): bool
    {
        if ((int)$expires < now()->timestamp) {
            return false; // Expired
        }

        $cleanPath = ltrim(preg_replace('/^storage\//', '', $path), '/');
        $appKey = config('app.key');
        $expectedSignature = hash_hmac('sha256', "{$expires}|{$cleanPath}", $appKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check if the current request/user has access to a specific Lesson.
     */
    public static function canAccessLesson(Lesson $lesson, $user = null): bool
    {
        if ($user === null) {
            $user = auth('sanctum')->user();
        }

        // 1. Admin / parent always have full access
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }
        if ($user && isset($user->role) && in_array(is_object($user->role) ? $user->role->value : $user->role, ['admin', 'parent'])) {
            return true;
        }

        // 2. If guest / unauthenticated, cannot access regular lessons
        if (!$user) {
            return false;
        }

        // 3. Check user subscription access for this lesson's unit
        $lesson->loadMissing(['unit.subject.section.grade']);
        $unit = $lesson->unit;
        if (!$unit) {
            return false;
        }

        return self::checkUserUnitAccess($unit, $user);
    }

    /**
     * Check if a lesson is the first active lesson of its unit.
     */
    public static function isFirstLessonOfUnit(Lesson $lesson): bool
    {
        return false;
    }

    /**
     * Check if the current request/user has access to a specific LessonFile.
     */
    public static function canAccessLessonFile(LessonFile $lessonFile, $user = null): bool
    {
        $lessonFile->loadMissing('lesson');
        $lesson = $lessonFile->lesson;
        if (!$lesson) {
            return false;
        }

        return self::canAccessLesson($lesson, $user);
    }

    /**
     * Check whether a given file path is permitted (free trial or registered).
     */
    public static function isFilePathFreeTrial(string $path): bool
    {
        $cleanPath = preg_replace('/^storage\//', '', $path);
        return FreeTrialLessonFile::where('file_path', $path)
            ->orWhere('file_path', $cleanPath)
            ->orWhere('file_path', 'storage/' . $cleanPath)
            ->exists();
    }

    /**
     * Check if a user has access to a unit based on packages or legacy subscription.
     */
    public static function checkUserUnitAccess(Unit $unit, $user): bool
    {
        $unit->loadMissing(['subject.section.grade']);

        $unitSubjectId = $unit->subject_id;
        $unitSectionId = $unit->subject?->section_id;
        $unitGradeId = $unit->subject?->section?->grade_id;
        $unitStageId = $unit->subject?->section?->grade?->educational_stage_id;

        // 1. Check Package-based access
        if ($user->package_id) {
            $package = $user->relationLoaded('package') ? $user->package : $user->package()->first();
            if ($package && ($package->is_active ?? true)) {
                $expiry = $user->subscription_expiry ?? $package->expiry_date;
                $isExpired = false;
                if ($expiry) {
                    $isExpired = Carbon::parse($expiry)->endOfDay()->isPast();
                }

                $isStatusActive = in_array(strtolower($user->subscription_status ?? ''), ['active', 'valid']);

                if (!$isExpired || $isStatusActive) {
                    if (self::isUnitWithinPackageScope($package, $unitStageId, $unitGradeId, $unitSectionId, $unitSubjectId)) {
                        return true;
                    }
                }
            }
        }

        // 2. Check Legacy subscription access (grade-based)
        $isSubscriptionValid = in_array(strtolower($user->subscription_status ?? ''), ['active', 'valid']) || 
                               ($user->subscription_expiry && Carbon::parse($user->subscription_expiry)->endOfDay()->isFuture());

        if ($isSubscriptionValid && $unitGradeId !== null && (int)$unitGradeId === (int)$user->grade_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if a unit matches the package scope.
     */
    public static function isUnitWithinPackageScope($package, $unitStageId, $unitGradeId, $unitSectionId, $unitSubjectId): bool
    {
        $scopeMeta = null;
        if (!empty($package->description) && str_contains($package->description, '<!--SCOPE_META:')) {
            if (preg_match('/<!--SCOPE_META:(.*?)-->/s', $package->description, $matches)) {
                $scopeMeta = json_decode($matches[1], true);
            }
        }

        if (is_array($scopeMeta)) {
            // Stage match
            $stageMatch = !empty($scopeMeta['is_all_stages']);
            if (!$stageMatch && !empty($scopeMeta['stage_ids']) && is_array($scopeMeta['stage_ids'])) {
                $stageMatch = $unitStageId !== null && in_array((string)$unitStageId, array_map('strval', $scopeMeta['stage_ids']));
            } elseif (!$stageMatch && !empty($package->educational_stage_id)) {
                $stageMatch = $unitStageId === null || ((int)$package->educational_stage_id === (int)$unitStageId);
            } else {
                $stageMatch = true;
            }

            // Grade match
            $gradeMatch = !empty($scopeMeta['is_all_grades']);
            if (!$gradeMatch && !empty($scopeMeta['grade_ids']) && is_array($scopeMeta['grade_ids'])) {
                $gradeMatch = $unitGradeId !== null && in_array((string)$unitGradeId, array_map('strval', $scopeMeta['grade_ids']));
            } elseif (!$gradeMatch && !empty($package->grade_id)) {
                $gradeMatch = $unitGradeId === null || ((int)$package->grade_id === (int)$unitGradeId);
            } else {
                $gradeMatch = true;
            }

            // Section match
            $sectionMatch = !empty($scopeMeta['is_all_sections']);
            if (!$sectionMatch && !empty($scopeMeta['section_ids']) && is_array($scopeMeta['section_ids'])) {
                $sectionMatch = $unitSectionId !== null && in_array((string)$unitSectionId, array_map('strval', $scopeMeta['section_ids']));
            } elseif (!$sectionMatch && !empty($package->section_id)) {
                $sectionMatch = $unitSectionId === null || ((int)$package->section_id === (int)$unitSectionId);
            } else {
                $sectionMatch = true;
            }

            // Subject match
            $subjectMatch = !empty($scopeMeta['is_all_subjects']);
            if (!$subjectMatch && !empty($scopeMeta['subject_ids']) && is_array($scopeMeta['subject_ids'])) {
                $subjectMatch = $unitSubjectId !== null && in_array((string)$unitSubjectId, array_map('strval', $scopeMeta['subject_ids']));
            } elseif (!$subjectMatch && !empty($package->subject_id)) {
                $subjectMatch = $unitSubjectId === null || ((int)$package->subject_id === (int)$unitSubjectId);
            } else {
                $subjectMatch = true;
            }

            return $stageMatch && $gradeMatch && $sectionMatch && $subjectMatch;
        }

        // Standard column-based matching
        if (!empty($package->educational_stage_id) && $unitStageId !== null) {
            if ((int)$package->educational_stage_id !== (int)$unitStageId) {
                return false;
            }
        }

        if (!empty($package->grade_id) && $unitGradeId !== null) {
            if ((int)$package->grade_id !== (int)$unitGradeId) {
                return false;
            }
        }

        if (!empty($package->section_id) && $unitSectionId !== null) {
            if ((int)$package->section_id !== (int)$unitSectionId) {
                return false;
            }
        }

        if (!empty($package->subject_id) && $unitSubjectId !== null) {
            if ((int)$package->subject_id !== (int)$unitSubjectId) {
                return false;
            }
        }

        return true;
    }
}
