<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Package;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    #[OA\Get(
        path: '/api/v1/users',
        operationId: 'getUsers',
        summary: 'Get all users',
        description: 'Returns a list of users, with optional search and role filtering.',
        tags: ['User Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Search term for name or email', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'role', in: 'query', required: false, description: 'Filter by role', schema: new OA\Schema(type: 'string', enum: ['admin', 'parent', 'student']))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserResource'))
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['stage', 'grade', 'package.educationalStage', 'package.grade', 'package.section', 'package.subject']);

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        $users = $query->orderBy('name')->get();

        return response()->json([
            'data' => UserResource::collection($users)
        ]);
    }

    /**
     * Display the specified user.
     */
    #[OA\Get(
        path: '/api/v1/users/{id}',
        operationId: 'getUserById',
        summary: 'Get a specific user',
        tags: ['User Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource')
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function show($id): JsonResponse
    {
        $user = User::with(['stage', 'grade', 'package.educationalStage', 'package.grade', 'package.section', 'package.subject'])->findOrFail($id);
        return response()->json([
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    #[OA\Post(
        path: '/api/v1/users',
        operationId: 'createUser',
        summary: 'Create a new user',
        tags: ['User Management'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'role'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'parent', 'student'], example: 'student'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource')
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'package_id' => 'nullable|integer|exists:packages,id',
            'stage_id' => 'nullable|integer|exists:educational_stages,id',
            'grade_id' => 'nullable|integer|exists:grades,id',
            'subscription_expiry' => 'nullable|date',
        ]);

        $packageId = $request->package_id;
        $stageId = $request->stage_id;
        $gradeId = $request->grade_id;
        $subscriptionExpiry = $request->subscription_expiry;

        if ($packageId) {
            $package = Package::find($packageId);
            if ($package) {
                $stageId = $package->educational_stage_id;
                $gradeId = $package->grade_id;
                if (!$subscriptionExpiry) {
                    $subscriptionExpiry = $package->expiry_date;
                }
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true),
            'password' => Hash::make($request->password),
            'package_id' => $packageId,
            'stage_id' => $stageId,
            'grade_id' => $gradeId,
            'subscription_expiry' => $subscriptionExpiry,
        ]);

        $user->load(['stage', 'grade', 'package.educationalStage', 'package.grade', 'package.section', 'package.subject']);

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Update the specified user in storage.
     */
    #[OA\Put(
        path: '/api/v1/users/{id}',
        operationId: 'updateUser',
        summary: 'Update a user',
        tags: ['User Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'parent', 'student']),
                    new OA\Property(property: 'is_active', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User updated successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource')
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|required|string',
            'package_id' => 'nullable|integer|exists:packages,id',
            'stage_id' => 'nullable|integer|exists:educational_stages,id',
            'grade_id' => 'nullable|integer|exists:grades,id',
            'subscription_expiry' => 'nullable|date',
        ]);

        $updateData = $request->only(['name', 'email', 'role', 'is_active', 'package_id', 'stage_id', 'grade_id', 'subscription_expiry']);

        if ($request->has('package_id')) {
            $packageId = $request->package_id;
            if ($packageId) {
                $package = Package::find($packageId);
                if ($package) {
                    $updateData['stage_id'] = $package->educational_stage_id;
                    $updateData['grade_id'] = $package->grade_id;
                    if (!$request->has('subscription_expiry') || !$request->subscription_expiry) {
                        $updateData['subscription_expiry'] = $package->expiry_date;
                    }
                }
            } else {
                $updateData['package_id'] = null;
            }
        }

        $user->update($updateData);
        $user->load(['stage', 'grade', 'package.educationalStage', 'package.grade', 'package.section', 'package.subject']);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    #[OA\Delete(
        path: '/api/v1/users/{id}',
        operationId: 'deleteUser',
        summary: 'Delete a user',
        tags: ['User Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'User deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Toggle the active status of a user.
     */
    #[OA\Post(
        path: '/api/v1/users/{id}/toggle-status',
        operationId: 'toggleUserStatus',
        summary: 'Toggle active status of a user',
        tags: ['User Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status updated successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User status updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource')
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function toggleStatus($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => 'User status updated',
            'data' => new UserResource($user)
        ]);
    }
}
