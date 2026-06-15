<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SectionService;
use App\Http\Resources\SectionResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SectionController extends Controller
{
    protected $service;

    public function __construct(SectionService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/sections',
        summary: 'Get all sections',
        tags: ['Sections'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return SectionResource::collection($this->service->getAllActive());
    }

    #[OA\Get(
        path: '/api/v1/sections/{id}',
        summary: 'Get a specific section',
        tags: ['Sections'],
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
        return new SectionResource($this->service->getById($id));
    }

    #[OA\Post(
        path: '/api/v1/sections',
        summary: 'Create a new section',
        tags: ['Sections'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'grade_id'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'القسم العلمي'),
                    new OA\Property(property: 'description', type: 'string', example: 'شعبة العلوم والرياضيات'),
                    new OA\Property(property: 'grade_id', type: 'integer', example: 1),
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
        return new SectionResource($model);
    }

    #[OA\Put(
        path: '/api/v1/sections/{id}',
        summary: 'Update a section',
        tags: ['Sections'],
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
                    new OA\Property(property: 'grade_id', type: 'integer'),
                    new OA\Property(property: 'order', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Section not found')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $model = $this->service->update($id, $data);
        return new SectionResource($model);
    }

    #[OA\Delete(
        path: '/api/v1/sections/{id}',
        summary: 'Delete a section',
        tags: ['Sections'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Section not found')
        ]
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
