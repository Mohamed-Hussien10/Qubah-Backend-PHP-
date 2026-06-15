<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EducationalStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? null,
            'thumbnail_url' => $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null,
            'background_image_url' => $this->background_image_path ? (str_starts_with($this->background_image_path, 'http') ? $this->background_image_path : url('storage/' . $this->background_image_path)) : null,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'grades_count' => $this->grades_count,
            'grades' => GradeResource::collection($this->whenLoaded('grades')),
            'created_at' => $this->created_at,
        ];
    }
}
