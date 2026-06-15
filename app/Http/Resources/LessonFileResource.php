<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LessonFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?? null,
            'thumbnail_url' => $this->thumbnail_path ? url('storage/' . $this->thumbnail_path) : null,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'type' => $this->type,
            'file_path' => $this->file_path,
            'file_url' => $this->file_path ? url($this->file_path) : null,
            'metadata' => $this->metadata,
            'lesson_id' => $this->lesson_id,
            'created_at' => $this->created_at,
        ];
    }
}
