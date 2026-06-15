<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\EducationalStageService;
use App\Http\Resources\EducationalStageResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EducationalStageController extends Controller
{
    protected $service;

    public function __construct(EducationalStageService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/educational-stages',
        summary: 'Get all educational stages',
        tags: ['Educational Stages'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return EducationalStageResource::collection($this->service->getAllActive());
    }

    #[OA\Get(
        path: '/api/v1/educational-stages/{id}',
        summary: 'Get a specific educational stage',
        tags: ['Educational Stages'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)
    {
        return new EducationalStageResource($this->service->getById($id));
    }

    #[OA\Post(
        path: '/api/v1/educational-stages',
        summary: 'Create a new educational stage',
        tags: ['Educational Stages'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'المرحلة الابتدائية'),
                    new OA\Property(property: 'description', type: 'string', example: 'مرحلة التعليم الابتدائي'),
                    new OA\Property(property: 'thumbnail_path', type: 'string', nullable: true, example: null),
                    new OA\Property(property: 'order', type: 'integer', example: 1),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->all();
        $model = $this->service->create($data);
        return new EducationalStageResource($model);
    }

    #[OA\Put(
        path: '/api/v1/educational-stages/{id}',
        summary: 'Update an educational stage',
        tags: ['Educational Stages'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'thumbnail_path', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Stage not found')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $model = $this->service->update($id, $data);
        return new EducationalStageResource($model);
    }

    #[OA\Delete(
        path: '/api/v1/educational-stages/{id}',
        summary: 'Delete an educational stage',
        tags: ['Educational Stages'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Stage not found')
        ]
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
