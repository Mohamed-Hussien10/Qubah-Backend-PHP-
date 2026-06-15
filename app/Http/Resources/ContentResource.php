<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContentResource',
    title: 'Content',
    description: 'Content item resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'topic_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Introduction to Addition'),
        new OA\Property(property: 'type', type: 'string', enum: ['video', 'audio', 'pdf', 'interactive'], example: 'video'),
        new OA\Property(property: 'file_path', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'url', type: 'string', nullable: true, example: 'https://www.w3schools.com/html/mov_bbb.mp4'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, example: null),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'topic', ref: '#/components/schemas/TopicResource', nullable: true, description: 'Loaded when fetching single content'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-20T09:00:00.000000Z'),
    ],
    type: 'object'
)]
class ContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic_id' => $this->topic_id,
            'title' => $this->title,
            'type' => $this->type->value,
            'file_path' => $this->file_path,
            'url' => $this->url,
            'metadata' => $this->metadata,
            'order' => $this->order,
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'created_at' => $this->created_at,
        ];
    }
}
