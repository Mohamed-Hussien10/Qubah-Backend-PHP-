<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\Package;
use App\Models\EducationalStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    protected array $arabicMonths = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];

    /**
     * Get user analytics data.
     * Endpoint: GET /api/v1/analytics/users
     */
    public function users(): JsonResponse
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newUsersThisMonth = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        // Real dynamic role counts from DB (only student and admin)
        $rolesMap = [
            'student' => 'طلاب',
            'admin' => 'مدراء',
        ];

        $counts = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role')
            ->toArray();

        $usersByRole = [];
        foreach ($rolesMap as $roleKey => $roleLabel) {
            $usersByRole[$roleLabel] = (int) ($counts[$roleKey] ?? 0);
        }
        foreach ($counts as $roleKey => $total) {
            if (!isset($rolesMap[$roleKey]) && !empty($roleKey)) {
                $usersByRole[$roleKey] = (int) $total;
            }
        }

        // Real dynamic 12-month user registration growth from database
        $monthlyGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthNum = (int) $date->format('n');
            $year = (int) $date->format('Y');

            $count = User::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->count();

            $monthlyGrowth[] = [
                'month' => $this->arabicMonths[$monthNum] ?? $date->format('M'),
                'value' => (double) $count,
            ];
        }

        return response()->json([
            'message' => 'User analytics retrieved successfully',
            'data' => [
                'total_users' => (int) $totalUsers,
                'active_users' => (int) $activeUsers,
                'new_users_this_month' => (int) $newUsersThisMonth,
                'users_by_role' => $usersByRole,
                'monthly_growth' => $monthlyGrowth,
            ]
        ]);
    }

    /**
     * Get content analytics data.
     * Endpoint: GET /api/v1/analytics/content
     */
    public function content(): JsonResponse
    {
        $totalLessons = Lesson::count();
        $totalDownloads = LessonFile::count();

        // Real content count per educational stage via DB join
        $stageCounts = DB::table('educational_stages')
            ->leftJoin('grades', function ($join) {
                $join->on('grades.educational_stage_id', '=', 'educational_stages.id')
                    ->whereNull('grades.deleted_at');
            })
            ->leftJoin('sections', function ($join) {
                $join->on('sections.grade_id', '=', 'grades.id')
                    ->whereNull('sections.deleted_at');
            })
            ->leftJoin('subjects', function ($join) {
                $join->on('subjects.section_id', '=', 'sections.id')
                    ->whereNull('subjects.deleted_at');
            })
            ->leftJoin('units', function ($join) {
                $join->on('units.subject_id', '=', 'subjects.id')
                    ->whereNull('units.deleted_at');
            })
            ->leftJoin('lessons', function ($join) {
                $join->on('lessons.unit_id', '=', 'units.id')
                    ->whereNull('lessons.deleted_at');
            })
            ->whereNull('educational_stages.deleted_at')
            ->groupBy('educational_stages.id', 'educational_stages.title')
            ->select('educational_stages.title', DB::raw('count(lessons.id) as total_lessons'))
            ->pluck('total_lessons', 'title')
            ->toArray();

        $contentByStage = [];
        foreach ($stageCounts as $stageTitle => $count) {
            $contentByStage[$stageTitle] = (int) $count;
        }

        if (empty($contentByStage)) {
            $contentByStage['المحتوى التعليمي'] = (int) $totalLessons;
        }

        return response()->json([
            'message' => 'Content analytics retrieved successfully',
            'data' => [
                'total_lessons' => (int) $totalLessons,
                'total_downloads' => (int) $totalDownloads,
                'most_viewed_lessons' => [],
                'content_by_stage' => $contentByStage,
            ]
        ]);
    }

    /**
     * Get revenue analytics data.
     * Endpoint: GET /api/v1/analytics/revenue
     */
    public function revenue(): JsonResponse
    {
        // Real monthly revenue calculated from user package subscriptions by created_at
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthNum = (int) $date->format('n');
            $year = (int) $date->format('Y');

            $revThisMonth = User::whereYear('users.created_at', $year)
                ->whereMonth('users.created_at', $monthNum)
                ->join('packages', 'packages.id', '=', 'users.package_id')
                ->sum('packages.price');

            $monthlyRevenue[] = [
                'month' => $this->arabicMonths[$monthNum] ?? $date->format('M'),
                'value' => (double) $revThisMonth,
            ];
        }

        // Include packages with subscribers for accurate subscription breakdown
        $packages = Package::withTrashed()->get();
        $subscriptionBreakdown = [];

        foreach ($packages as $package) {
            $subscribers = User::where('package_id', $package->id)->count();
            if ($subscribers > 0 || $package->is_active) {
                $pkgRevenue = (double) ($subscribers * ($package->price ?? 0));
                $subscriptionBreakdown[$package->name] = (double) $pkgRevenue;
            }
        }

        if (empty($subscriptionBreakdown)) {
            $subscriptionBreakdown['لا توجد باقات'] = 0.0;
        }

        // Calculate total revenue consistently from user packages
        $totalRevenue = (double) User::join('packages', 'packages.id', '=', 'users.package_id')
            ->sum('packages.price');

        // Ensure total revenue is strictly consistent with monthly revenue bars
        $sumOfMonthly = (double) array_sum(array_column($monthlyRevenue, 'value'));
        if ($sumOfMonthly > $totalRevenue) {
            $totalRevenue = $sumOfMonthly;
        }

        return response()->json([
            'message' => 'Revenue analytics retrieved successfully',
            'data' => [
                'total_revenue' => (double) $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'subscription_breakdown' => $subscriptionBreakdown,
            ]
        ]);
    }

    /**
     * Dashboard Stats for /dashboard/stats
     */
    public function dashboardStats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalLessons = Lesson::count();

        $lastMonthUsers = User::where('created_at', '<', Carbon::now()->startOfMonth())
            ->where('created_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->count();
        $thisMonthUsers = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        $growth = 0.0;
        if ($lastMonthUsers > 0) {
            $growth = (($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100;
        } elseif ($thisMonthUsers > 0) {
            $growth = 100.0;
        }

        return response()->json([
            'message' => 'Dashboard stats retrieved',
            'data' => [
                'total_users' => (int) $totalUsers,
                'total_lessons' => (int) $totalLessons,
                'user_growth_percent' => (double) round($growth, 1),
            ]
        ]);
    }

    /**
     * Dashboard User Growth for /dashboard/users
     */
    public function dashboardUsers(): JsonResponse
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthNum = (int) $date->format('n');
            $year = (int) $date->format('Y');

            $count = User::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->count();

            $data[] = [
                'label' => $this->arabicMonths[$monthNum] ?? $date->format('M'),
                'value' => (double) $count,
            ];
        }

        return response()->json([
            'message' => 'Dashboard users growth retrieved',
            'data' => $data,
        ]);
    }

    /**
     * Dashboard Activity for /dashboard/activity
     * Combines real recent activities from users, lessons, and packages
     */
    public function dashboardActivity(): JsonResponse
    {
        Carbon::setLocale('ar');
        $activities = [];

        // Real latest users
        $latestUsers = User::latest('created_at')->take(5)->get();
        foreach ($latestUsers as $u) {
            $roleLabel = match ($u->role?->value ?? $u->role) {
                'admin' => 'مدير',
                default => 'طالب',
            };

            $activities[] = [
                'icon' => 'person_add',
                'color' => 'primary',
                'title' => 'مستخدم جديد: ' . $u->name,
                'description' => 'البريد: ' . $u->email . ' (' . $roleLabel . ')',
                'time' => $u->created_at ? $u->created_at->locale('ar')->diffForHumans() : 'مؤخراً',
                'timestamp' => $u->created_at ? $u->created_at->timestamp : 0,
            ];
        }

        // Real latest lessons
        $latestLessons = Lesson::latest('created_at')->take(5)->get();
        foreach ($latestLessons as $l) {
            $activities[] = [
                'icon' => 'menu_book',
                'color' => 'info',
                'title' => 'تمت إضافة درس جديد: ' . $l->title,
                'description' => $l->description ?: 'درس تعليمي مضاف حديثاً',
                'time' => $l->created_at ? $l->created_at->locale('ar')->diffForHumans() : 'مؤخراً',
                'timestamp' => $l->created_at ? $l->created_at->timestamp : 0,
            ];
        }

        // Real latest packages
        $latestPackages = Package::latest('created_at')->take(3)->get();
        foreach ($latestPackages as $p) {
            $activities[] = [
                'icon' => 'card_membership',
                'color' => 'success',
                'title' => 'باقة اشتراك جديدة: ' . $p->name,
                'description' => 'السعر: ' . $p->price . ' ر.ع.',
                'time' => $p->created_at ? $p->created_at->locale('ar')->diffForHumans() : 'مؤخراً',
                'timestamp' => $p->created_at ? $p->created_at->timestamp : 0,
            ];
        }

        // Sort all real activities by timestamp descending
        usort($activities, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        // Return up to 7 most recent real events
        $activities = array_slice($activities, 0, 7);

        return response()->json([
            'message' => 'Recent activity retrieved',
            'data' => $activities,
        ]);
    }
}
