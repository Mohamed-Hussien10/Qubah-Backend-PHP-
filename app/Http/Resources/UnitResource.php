<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lessons = $this->whenLoaded('lessons');
        
        $user = auth('sanctum')->user();
        if ($user && $user->role->value === 'student' && $lessons instanceof \Illuminate\Support\Collection) {
            // Check if unit's grade matches the user's grade
            // Unit -> Subject -> Section -> Grade
            $unitGradeId = $this->subject?->section?->grade_id;
            
            $isSubscriptionValid = $user->subscription_status === 'active' || 
                                   ($user->subscription_expiry && \Carbon\Carbon::parse($user->subscription_expiry)->isFuture());
            
            if ($unitGradeId !== $user->grade_id || !$isSubscriptionValid) {
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
}
