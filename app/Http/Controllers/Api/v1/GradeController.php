<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\GradeService;
use App\Http\Resources\GradeResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GradeController extends Controller
{
    protected $service;

    public function __construct(GradeService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/grades',
        summary: 'Get all grades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return GradeResource::collection($this->service->getAllActive());
    }

    #[OA\Get(
        path: '/api/v1/grades/{id}',
        summary: 'Get a specific grade',
        tags: ['Grades'],
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
        return new GradeResource($this->service->getById($id));
    }

    #[OA\Post(
        path: '/api/v1/grades',
        summary: 'Create a new grade',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'educational_stage_id'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'الصف الأول الابتدائي'),
                    new OA\Property(property: 'description', type: 'string', example: 'أولى ابتدائي'),
                    new OA\Property(property: 'educational_stage_id', type: 'integer', example: 1),
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
        return new GradeResource($model);
    }

    #[OA\Put(
        path: '/api/v1/grades/{id}',
        summary: 'Update a grade',
        tags: ['Grades'],
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
                    new OA\Property(property: 'educational_stage_id', type: 'integer'),
                    new OA\Property(property: 'order', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Grade not found')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $model = $this->service->update($id, $data);
        return new GradeResource($model);
    }

    #[OA\Delete(
        path: '/api/v1/grades/{id}',
        summary: 'Delete a grade',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Grade not found')
        ]
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
