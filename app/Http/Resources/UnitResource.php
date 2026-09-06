<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lessons = $this->whenLoaded('lessons');
        
        $user = auth('sanctum')->user();
        if ($user && $user->role->value === 'student' && $lessons instanceof \Illuminate\Support\Collection) {
            if (!$this->checkStudentAccess($user)) {
                // Return only the first lesson (sorted by order if possible, or just first element)
                $lessons = collect([$lessons->first()])->filter(); 
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? null,
            'thumbnail_url' => $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'lessons_count' => $this->lessons_count,
            'lessons' => LessonResource::collection($lessons),
            'subject_id' => $this->subject_id,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Determine whether the student has full access to this unit's lessons.
     */
    protected function checkStudentAccess($user): bool
    {
        $this->loadMissing(['subject.section.grade']);

        $unitSubjectId = $this->subject_id;
        $unitSectionId = $this->subject?->section_id;
        $unitGradeId = $this->subject?->section?->grade_id;
        $unitStageId = $this->subject?->section?->grade?->educational_stage_id;

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
                    if ($this->isUnitWithinPackageScope($package, $unitStageId, $unitGradeId, $unitSectionId, $unitSubjectId)) {
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
     * Check if a unit matches the package scope (including scope metadata or columns).
     */
    protected function isUnitWithinPackageScope($package, $unitStageId, $unitGradeId, $unitSectionId, $unitSubjectId): bool
    {
        // Check for embedded JSON scope metadata in package description
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
