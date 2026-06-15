<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    #[OA\Post(
        path: '/api/v1/auth/register',
        operationId: 'register',
        summary: 'Register a new user',
        description: 'Creates a new user account and returns an authentication token.',
        tags: ['Authentication'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 255),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123', minLength: 8),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'parent', 'student'], example: 'student', description: 'Optional. Defaults to student.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                                new OA\Property(property: 'access_token', type: 'string', example: '1|abc123token...'),
                                new OA\Property(property: 'refresh_token', type: 'string', example: '1|abc123token...'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? \App\Enums\UserRole::Student->value,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'refresh_token' => $token, // Simplified for now, real implementation would use proper refresh tokens
            ]
        ], 201);
    }

    /**
     * Authenticate user and issue token.
     */
    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'login',
        summary: 'Login',
        description: 'Authenticate a user with email and password. Returns user data and a Bearer token.',
        tags: ['Authentication'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'student@qubah.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged in successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged in successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                                new OA\Property(property: 'access_token', type: 'string', example: '2|xyz789token...'),
                                new OA\Property(property: 'refresh_token', type: 'string', example: '2|xyz789token...'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid credentials'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens (optional, for security)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load(['stage', 'grade']);

        return response()->json([
            'message' => 'Logged in successfully',
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'refresh_token' => $token,
            ]
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'logout',
        summary: 'Logout',
        description: 'Invalidate the current access token.',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully logged out',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    #[OA\Get(
        path: '/api/v1/auth/me',
        operationId: 'me',
        summary: 'Get current user info',
        description: 'Returns the currently authenticated user details.',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Profile retrieved successfully'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['stage', 'grade']);
        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data' => [
                'user' => new UserResource($user),
            ]
        ]);
    }
}
