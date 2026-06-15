<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TopicResource',
    title: 'Topic',
    description: 'Topic resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'subject_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Addition and Subtraction'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Learn how to add and subtract numbers.'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'contents_count', type: 'integer', nullable: true, example: 5),
        new OA\Property(
            property: 'contents',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ContentResource'),
            nullable: true,
            description: 'Loaded when fetching a single topic'
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-20T09:00:00.000000Z'),
    ],
    type: 'object'
)]
class TopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'contents_count' => $this->whenCounted('contents'),
            'contents' => ContentResource::collection($this->whenLoaded('contents')),
            'created_at' => $this->created_at,
        ];
    }
}
