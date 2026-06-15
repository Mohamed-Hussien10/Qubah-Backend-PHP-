<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserProgressResource',
    title: 'User Progress',
    description: 'User progress tracking resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 3),
        new OA\Property(property: 'content_id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['started', 'completed'], example: 'completed'),
        new OA\Property(property: 'time_spent', type: 'integer', example: 300, description: 'Seconds spent on this content'),
        new OA\Property(property: 'score', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'content', ref: '#/components/schemas/ContentResource', nullable: true, description: 'Loaded when included'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-20T09:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-20T09:10:00.000000Z'),
    ],
    type: 'object'
)]
class UserProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'content_id' => $this->content_id,
            'status' => $this->status->value,
            'time_spent' => $this->time_spent,
            'score' => $this->score,
            'content' => new ContentResource($this->whenLoaded('content')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
