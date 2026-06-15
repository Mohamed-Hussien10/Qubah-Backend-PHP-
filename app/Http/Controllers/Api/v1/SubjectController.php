<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SubjectService;
use App\Http\Resources\SubjectResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubjectController extends Controller
{
    protected $service;

    public function __construct(SubjectService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/subjects',
        summary: 'Get all subjects',
        tags: ['Subjects'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return SubjectResource::collection($this->service->getAllActive());
    }

    #[OA\Get(
        path: '/api/v1/subjects/{id}',
        summary: 'Get a specific subject',
        tags: ['Subjects'],
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
        return new SubjectResource($this->service->getById($id));
    }

    #[OA\Post(
        path: '/api/v1/subjects',
        summary: 'Create a new subject',
        tags: ['Subjects'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'section_id'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'الرياضيات'),
                    new OA\Property(property: 'description', type: 'string', example: 'مادة الرياضيات للصف الأول'),
                    new OA\Property(property: 'section_id', type: 'integer', example: 1),
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
        return new SubjectResource($model);
    }

    #[OA\Put(
        path: '/api/v1/subjects/{id}',
        summary: 'Update a subject',
        tags: ['Subjects'],
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
                    new OA\Property(property: 'section_id', type: 'integer'),
                    new OA\Property(property: 'thumbnail_path', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Subject not found')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $model = $this->service->update($id, $data);
        return new SubjectResource($model);
    }

    #[OA\Delete(
        path: '/api/v1/subjects/{id}',
        summary: 'Delete a subject',
        tags: ['Subjects'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Subject not found')
        ]
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
