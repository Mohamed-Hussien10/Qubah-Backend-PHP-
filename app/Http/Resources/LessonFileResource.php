<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Services\Access\ContentAccessService;

class LessonFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasAccess = ContentAccessService::canAccessLessonFile($this->resource);

        $fileUrl = null;
        if ($hasAccess && $this->file_path) {
            if (str_starts_with($this->file_path, 'http')) {
                $fileUrl = $this->file_path;
            } else {
                // Generate a path-based secure URL that cleanly ends with .zip or .mp4
                // e.g. /api/v1/files/s/{signature}/{expires}/lesson_files/xxx.zip
                $fileUrl = ContentAccessService::generateSecureFileUrl($this->file_path, 7);
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? null,
            'thumbnail_url' => $this->thumbnail_path ? url('storage/' . $this->thumbnail_path) : null,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'has_access' => $hasAccess,
            'type' => $this->type,
            'file_path' => $hasAccess ? $this->file_path : null,
            'file_url' => $fileUrl,
            'metadata' => $this->metadata,
            'lesson_id' => $this->lesson_id,
            'created_at' => $this->created_at,
        ];
    }
}
