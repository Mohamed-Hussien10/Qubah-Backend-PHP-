<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    /**
     * Get dashboard aggregate stats.
     */
    #[OA\Get(
        path: '/api/v1/dashboard/stats',
        operationId: 'getDashboardStats',
        summary: 'Get dashboard aggregate statistics',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'totalUsers', type: 'integer'),
                    new OA\Property(property: 'activeSubscriptions', type: 'integer'),
                    new OA\Property(property: 'totalLessons', type: 'integer'),
                    new OA\Property(property: 'totalRevenue', type: 'number', format: 'float'),
                    new OA\Property(property: 'userGrowthPercent', type: 'number', format: 'float'),
                    new OA\Property(property: 'revenueGrowthPercent', type: 'number', format: 'float')
                ])
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function getStats(): JsonResponse
    {
        $totalUsers = User::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalLessons = Lesson::count();
        $totalRevenue = Subscription::sum('amount');

        return response()->json([
            'data' => [
                'totalUsers' => $totalUsers,
                'activeSubscriptions' => $activeSubscriptions,
                'totalLessons' => $totalLessons,
                'totalRevenue' => (float) $totalRevenue,
                'userGrowthPercent' => 12.5,
                'revenueGrowthPercent' => 8.2,
            ]
        ]);
    }

    /**
     * Get monthly revenue data for charting.
     */
    #[OA\Get(
        path: '/api/v1/dashboard/revenue',
        operationId: 'getDashboardRevenue',
        summary: 'Get monthly revenue charting data',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'value', type: 'number', format: 'float')
                ]))
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function getRevenueData(): JsonResponse
    {
        $data = [
            ['label' => 'يناير', 'value' => 12000.0],
            ['label' => 'فبراير', 'value' => 15000.0],
            ['label' => 'مارس', 'value' => 18000.0],
            ['label' => 'أبريل', 'value' => 22000.0],
            ['label' => 'مايو', 'value' => 25000.0],
            ['label' => 'يونيو', 'value' => 30000.0],
            ['label' => 'يوليو', 'value' => 35000.0],
            ['label' => 'أغسطس', 'value' => 40000.0],
            ['label' => 'سبتمبر', 'value' => 45000.0],
            ['label' => 'أكتوبر', 'value' => 50000.0],
            ['label' => 'نوفمبر', 'value' => 55000.0],
            ['label' => 'ديسمبر', 'value' => 60000.0],
        ];

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Get user growth data for charting.
     */
    #[OA\Get(
        path: '/api/v1/dashboard/users',
        operationId: 'getDashboardUsers',
        summary: 'Get monthly user growth charting data',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'value', type: 'integer')
                ]))
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function getUserGrowthData(): JsonResponse
    {
        $data = [
            ['label' => 'يناير', 'value' => 120],
            ['label' => 'فبراير', 'value' => 150],
            ['label' => 'مارس', 'value' => 200],
            ['label' => 'أبريل', 'value' => 280],
            ['label' => 'مايو', 'value' => 350],
            ['label' => 'يونيو', 'value' => 480],
            ['label' => 'يوليو', 'value' => 600],
            ['label' => 'أغسطس', 'value' => 750],
            ['label' => 'سبتمبر', 'value' => 900],
            ['label' => 'أكتوبر', 'value' => 1100],
            ['label' => 'نوفمبر', 'value' => 1350],
            ['label' => 'ديسمبر', 'value' => 1600],
        ];

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Get recent admin panel activity.
     */
    #[OA\Get(
        path: '/api/v1/dashboard/activity',
        operationId: 'getDashboardActivity',
        summary: 'Get recent admin panel activities',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'time', type: 'string'),
                    new OA\Property(property: 'icon', type: 'string')
                ]))
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function getRecentActivity(): JsonResponse
    {
        $activities = [
            [
                'id' => '1',
                'title' => 'مستخدم جديد',
                'description' => 'سجل محمد أحمد كطالب جديد في المنصة',
                'time' => 'منذ ٥ دقائق',
                'icon' => 'person_add',
            ],
            [
                'id' => '2',
                'title' => 'اشتراك جديد',
                'description' => 'اشترك خالد علي في الباقة السنوية',
                'time' => 'منذ ٢٠ دقيقة',
                'icon' => 'payment',
            ],
            [
                'id' => '3',
                'title' => 'إضافة درس',
                'description' => 'أضاف الأستاذ أحمد درساً جديداً في مادة الرياضيات',
                'time' => 'منذ ساعة',
                'icon' => 'add_task',
            ],
        ];

        return response()->json([
            'data' => $activities
        ]);
    }
}
