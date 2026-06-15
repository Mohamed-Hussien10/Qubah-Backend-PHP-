<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubscriptionController extends Controller
{
    // ── Plans Endpoints ──────────────────────────────────────────────────

    /**
     * Display a listing of plans.
     */
    #[OA\Get(
        path: '/api/v1/subscriptions/plans',
        operationId: 'getPlans',
        summary: 'Get all subscription plans',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function getAllPlans(): JsonResponse
    {
        $plans = Plan::all();
        return response()->json([
            'data' => $plans
        ]);
    }

    /**
     * Store a newly created plan.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/plans',
        operationId: 'createPlan',
        summary: 'Create a new subscription plan',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'duration_months'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Premium Plan'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                    new OA\Property(property: 'duration_months', type: 'integer', example: 12),
                    new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Successful operation'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function createPlan(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'duration_months' => 'required|integer',
            'features' => 'nullable|array',
        ]);

        $plan = Plan::create([
            'name' => $request->name,
            'price' => $request->price,
            'duration_months' => $request->duration_months,
            'features' => $request->features,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'data' => $plan
        ], 201);
    }

    /**
     * Update the specified plan.
     */
    #[OA\Put(
        path: '/api/v1/subscriptions/plans/{id}',
        operationId: 'updatePlan',
        summary: 'Update a subscription plan',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'price', type: 'number', format: 'float'),
                    new OA\Property(property: 'duration_months', type: 'integer'),
                    new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'is_active', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 404, description: 'Plan not found')
        ]
    )]
    public function updatePlan(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'duration_months' => 'sometimes|required|integer',
            'features' => 'nullable|array',
        ]);

        $plan->update($request->all());

        return response()->json([
            'data' => $plan
        ]);
    }

    /**
     * Remove the specified plan.
     */
    #[OA\Delete(
        path: '/api/v1/subscriptions/plans/{id}',
        operationId: 'deletePlan',
        summary: 'Delete a subscription plan',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 404, description: 'Plan not found')
        ]
    )]
    public function deletePlan($id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted successfully'
        ]);
    }

    // ── Subscriptions Endpoints ──────────────────────────────────────────

    /**
     * Display a listing of subscriptions.
     */
    #[OA\Get(
        path: '/api/v1/subscriptions',
        operationId: 'getSubscriptions',
        summary: 'Get all user subscriptions',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function getAllSubscriptions(): JsonResponse
    {
        $subscriptions = Subscription::all();
        return response()->json([
            'data' => $subscriptions
        ]);
    }

    /**
     * Assign a subscription to a user.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions',
        operationId: 'assignSubscription',
        summary: 'Assign a subscription package to a user',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'user_name', 'plan_name', 'start_date', 'end_date', 'amount'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 3),
                    new OA\Property(property: 'user_name', type: 'string', example: 'Timmy Student'),
                    new OA\Property(property: 'plan_name', type: 'string', example: 'Yearly Plan'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 450.00)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Successful operation'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function assignSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'user_name' => 'required|string',
            'plan_name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'amount' => 'required|numeric',
        ]);

        $subscription = Subscription::create([
            'user_id' => $request->user_id,
            'user_name' => $request->user_name,
            'plan_name' => $request->plan_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'amount' => $request->amount,
            'status' => 'active',
        ]);

        // Also update user subscription status
        $user = User::find($request->user_id);
        if ($user) {
            $user->subscription_status = 'active';
            $user->save();
        }

        return response()->json([
            'data' => $subscription
        ], 201);
    }

    /**
     * Update the specified subscription.
     */
    #[OA\Put(
        path: '/api/v1/subscriptions/{id}',
        operationId: 'updateSubscription',
        summary: 'Update a user subscription record',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'plan_name', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired', 'cancelled']),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 404, description: 'Subscription not found')
        ]
    )]
    public function updateSubscription(Request $request, $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update($request->all());

        return response()->json([
            'data' => $subscription
        ]);
    }

    /**
     * Cancel the subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/{id}/cancel',
        operationId: 'cancelSubscription',
        summary: 'Cancel an active user subscription',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 404, description: 'Subscription not found')
        ]
    )]
    public function cancelSubscription($id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->status = 'cancelled';
        $subscription->save();

        if ($subscription->user_id) {
            $user = User::find($subscription->user_id);
            if ($user) {
                $user->subscription_status = 'cancelled';
                $user->save();
            }
        }

        return response()->json([
            'data' => $subscription
        ]);
    }

    /**
     * Delete the subscription.
     */
    #[OA\Delete(
        path: '/api/v1/subscriptions/{id}',
        operationId: 'deleteSubscription',
        summary: 'Delete a subscription record',
        tags: ['Plans & Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 404, description: 'Subscription not found')
        ]
    )]
    public function deleteSubscription($id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully'
        ]);
    }
}
