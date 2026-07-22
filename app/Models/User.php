<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login',
        'stage_id',
        'grade_id',
        'package_id',
        'subscription_expiry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login' => 'datetime',
            'subscription_expiry' => 'date',
        ];
    }

    // ── Role Helpers ──────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isParent(): bool
    {
        return $this->role === UserRole::Parent_;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * Children linked to this parent.
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_child', 'parent_id', 'child_id')
                    ->withTimestamps();
    }

    /**
     * Parents linked to this child.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_child', 'child_id', 'parent_id')
                    ->withTimestamps();
    }

    public function stage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EducationalStage::class, 'stage_id');
    }

    public function grade(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function package(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
