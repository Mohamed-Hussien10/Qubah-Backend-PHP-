<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Services\Access\ContentAccessService;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lessons = $this->whenLoaded('lessons');
        
        $user = auth('sanctum')->user();
        $isAdminOrParent = $user && in_array(is_object($user->role) ? $user->role->value : $user->role, ['admin', 'parent']);
        $hasUnitAccess = $isAdminOrParent || ($user && ContentAccessService::checkUserUnitAccess($this->resource, $user));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? null,
            'thumbnail_url' => $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'has_access' => $hasUnitAccess,
            'lessons_count' => $this->lessons_count,
            'lessons' => LessonResource::collection($lessons),
            'subject_id' => $this->subject_id,
            'created_at' => $this->created_at,
        ];
    }
}
