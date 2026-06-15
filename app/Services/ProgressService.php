<?php

namespace App\Services;

use App\Enums\ProgressStatus;
use App\Models\Content;
use App\Models\UserProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class ProgressService
{
    /**
     * Update or create a progress record for a user on specific content.
     */
    public function updateProgress(User $user, int $contentId, array $data): UserProgress
    {
        // Verify content exists
        Content::findOrFail($contentId);

        return UserProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'content_id' => $contentId,
            ],
            [
                'status' => $data['status'] ?? ProgressStatus::Started->value,
                'time_spent' => $data['time_spent'] ?? 0,
                'score' => $data['score'] ?? null,
            ]
        );
    }

    /**
     * Get a summary of the user's learning progress.
     */
    public function getUserProgressSummary(User $user): array
    {
        $progress = $user->progress()->with('content.topic.subject')->get();

        $totalContents = Content::count();
        $completedContents = $progress->where('status', ProgressStatus::Completed)->count();
        $totalTimeSpent = $progress->sum('time_spent');

        return [
            'total_contents' => $totalContents,
            'completed_contents' => $completedContents,
            'completion_percentage' => $totalContents > 0
                ? round(($completedContents / $totalContents) * 100, 2)
                : 0,
            'total_time_spent' => $totalTimeSpent,
            'recent_activity' => $progress->sortByDesc('updated_at')->take(10)->values(),
        ];
    }

    /**
     * Get progress for a specific user (used by parent to view child's progress).
     */
    public function getProgressForUser(int $userId): Collection
    {
        return UserProgress::where('user_id', $userId)
            ->with('content.topic.subject')
            ->get();
    }
}
