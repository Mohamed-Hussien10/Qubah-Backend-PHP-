<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProgressRequest;
use App\Http\Resources\UserProgressResource;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProgressController extends Controller
{
    protected $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Update or create progress for a specific content.
     */
    #[OA\Post(
        path: '/api/v1/progress/{contentId}',
        operationId: 'updateProgress',
        summary: 'Update learning progress',
        description: 'Creates or updates progress for the authenticated user on a specific content item.',
        tags: ['Progress'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'contentId',
                in: 'path',
                required: true,
                description: 'Content ID to track progress for',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['started', 'completed'], example: 'completed'),
                    new OA\Property(property: 'time_spent', type: 'integer', minimum: 0, example: 300, description: 'Time spent in seconds'),
                    new OA\Property(property: 'score', type: 'integer', minimum: 0, nullable: true, example: 85, description: 'Optional score'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Progress updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/UserProgressResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateProgressRequest $request, int $contentId): UserProgressResource
    {
        $progress = $this->progressService->updateProgress(
            $request->user(),
            $contentId,
            $request->validated()
        );

        return new UserProgressResource($progress);
    }

    /**
     * Get a summary of the authenticated user's progress.
     */
    #[OA\Get(
        path: '/api/v1/progress/summary',
        operationId: 'getProgressSummary',
        summary: 'Get progress summary',
        description: 'Returns an overview of the authenticated user\'s learning progress, including total/completed content counts and recent activity.',
        tags: ['Progress'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Progress summary',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_contents', type: 'integer', example: 10),
                                new OA\Property(property: 'completed', type: 'integer', example: 5),
                                new OA\Property(property: 'started', type: 'integer', example: 3),
                                new OA\Property(property: 'total_time_spent', type: 'integer', example: 3600, description: 'Total seconds'),
                                new OA\Property(
                                    property: 'recent_activity',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/UserProgressResource')
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->progressService->getUserProgressSummary($request->user());
        
        // Convert recent activity to resources
        $summary['recent_activity'] = UserProgressResource::collection($summary['recent_activity']);

        return response()->json([
            'data' => $summary
        ]);
    }
}
