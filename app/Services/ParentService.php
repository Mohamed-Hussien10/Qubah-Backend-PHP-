<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ParentService
{
    /**
     * Get all children linked to a parent user.
     */
    public function getChildren(User $parent): Collection
    {
        return $parent->children()->get();
    }

    /**
     * Get a specific child's details (verifying the parent-child link).
     */
    public function getChild(User $parent, int $childId): User
    {
        return $parent->children()->where('child_id', $childId)->firstOrFail();
    }

    /**
     * Link a child to a parent.
     */
    public function linkChild(User $parent, int $childId): void
    {
        $child = User::where('id', $childId)
            ->where('role', UserRole::Student->value)
            ->firstOrFail();

        $parent->children()->syncWithoutDetaching([$child->id]);
    }
}
