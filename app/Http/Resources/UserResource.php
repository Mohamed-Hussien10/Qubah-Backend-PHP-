<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResource',
    title: 'User',
    description: 'User resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Timmy Student'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'student@qubah.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'parent', 'student'], example: 'student'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-20T09:00:00.000000Z'),
    ],
    type: 'object'
)]
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'is_active' => $this->is_active ?? true,
            'subscription_status' => $this->subscription_status ?? 'none',
            'stage_id' => $this->stage_id,
            'grade_id' => $this->grade_id,
            'package_id' => $this->package_id,
            'stage' => new EducationalStageResource($this->whenLoaded('stage')),
            'grade' => new GradeResource($this->whenLoaded('grade')),
            'package' => $this->whenLoaded('package'),
            'subscription_expiry' => $this->subscription_expiry ? $this->subscription_expiry->toIso8601String() : null,
            'last_login' => $this->last_login ? $this->last_login->toIso8601String() : null,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
        ];
    }
}
