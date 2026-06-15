<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\LessonService;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LessonController extends Controller
{
    protected $service;

    public function __construct(LessonService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/lessons',
        summary: 'Get all lessons',
        tags: ['Lessons'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return LessonResource::collection($this->service->getAllActive());
    }

    #[OA\Get(
        path: '/api/v1/lessons/{id}',
        summary: 'Get a specific lesson',
        tags: ['Lessons'],
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
        return new LessonResource($this->service->getById($id));
    }

    #[OA\Post(
        path: '/api/v1/lessons',
        summary: 'Create a new lesson',
        tags: ['Lessons'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'unit_id'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'الدرس الأول: مقدمة'),
                    new OA\Property(property: 'description', type: 'string', example: 'مقدمة في الوحدة الأولى'),
                    new OA\Property(property: 'unit_id', type: 'integer', example: 1),
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
        return new LessonResource($model);
    }

    #[OA\Put(
        path: '/api/v1/lessons/{id}',
        summary: 'Update a lesson',
        tags: ['Lessons'],
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
                    new OA\Property(property: 'unit_id', type: 'integer'),
                    new OA\Property(property: 'thumbnail_path', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Lesson not found')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $model = $this->service->update($id, $data);
        return new LessonResource($model);
    }

    #[OA\Delete(
        path: '/api/v1/lessons/{id}',
        summary: 'Delete a lesson',
        tags: ['Lessons'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Lesson not found')
        ]
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
