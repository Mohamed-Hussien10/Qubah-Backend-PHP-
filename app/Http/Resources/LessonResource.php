<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? null,
            'thumbnail_url' => $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'lesson_files_count' => $this->lesson_files_count,
            'lessonFiles' => LessonFileResource::collection($this->whenLoaded('lessonFiles')),
            'unit_id' => $this->unit_id,
            'created_at' => $this->created_at,
        ];
    }
}
