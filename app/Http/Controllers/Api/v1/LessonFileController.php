<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\LessonFileService;
use App\Http\Resources\LessonFileResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LessonFileController extends Controller
{
    protected $service;

    public function __construct(LessonFileService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/api/v1/lesson-files',
        summary: 'Get all lesson files',
        tags: ['Lesson Files'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return LessonFileResource::collection($this->service->getAllActive());
    }

    #[OA\Get(
        path: '/api/v1/lesson-files/{id}',
        summary: 'Get a specific lesson file',
        tags: ['Lesson Files'],
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
        return new LessonFileResource($this->service->getById($id));
    }

    #[OA\Post(
        path: '/api/v1/lesson-files',
        summary: 'Create a new lesson file',
        tags: ['Lesson Files'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'lesson_id', 'type', 'file_path'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'ملف الفيديو الرئيسي'),
                    new OA\Property(property: 'type', type: 'string', example: 'video'),
                    new OA\Property(property: 'file_path', type: 'string', example: 'files/lesson1.mp4'),
                    new OA\Property(property: 'lesson_id', type: 'integer', example: 1),
                    new OA\Property(property: 'thumbnail_path', type: 'string', nullable: true, example: null),
                    new OA\Property(property: 'metadata', type: 'object', nullable: true),
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
        return new LessonFileResource($model);
    }

    #[OA\Post(
        path: '/api/v1/lesson-files/upload',
        summary: 'Upload a new lesson file',
        tags: ['Lesson Files'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file', 'lesson_id', 'title', 'type'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'lesson_id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'type', type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created successfully'),
            new OA\Response(response: 400, description: 'No file provided'),
        ]
    )]
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'lesson_id' => 'required',
            'title' => 'required|string',
            'type' => 'required|string',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            
            // Store file in 'public/lesson_files'
            $path = $file->store('lesson_files', 'public');
            
            $data = $request->only(['lesson_id', 'title', 'type']);
            $data['file_path'] = 'storage/' . $path;
            
            // Add metadata
            $data['metadata'] = [
                'file_size' => round($file->getSize() / 1048576, 2) . ' MB',
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType()
            ];

            $model = $this->service->create($data);
            return new LessonFileResource($model);
        }

        return response()->json([
            'success' => false, 
            'message' => 'No file provided.'
        ], 400);
    }

    #[OA\Put(
        path: '/api/v1/lesson-files/{id}',
        summary: 'Update a lesson file',
        tags: ['Lesson Files'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'file_path', type: 'string'),
                    new OA\Property(property: 'lesson_id', type: 'integer'),
                    new OA\Property(property: 'thumbnail_path', type: 'string', nullable: true),
                    new OA\Property(property: 'metadata', type: 'object', nullable: true),
                    new OA\Property(property: 'order', type: 'integer'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Lesson file not found')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $model = $this->service->update($id, $data);
        return new LessonFileResource($model);
    }

    #[OA\Delete(
        path: '/api/v1/lesson-files/{id}',
        summary: 'Delete a lesson file',
        tags: ['Lesson Files'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Lesson file not found')
        ]
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }
}
