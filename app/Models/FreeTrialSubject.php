<?php

namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FreeTrialSubject extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['free_trial_grade_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    protected $appends = ['thumbnail_url'];

    public function getThumbnailUrlAttribute() {
        return $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null;
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (FreeTrialSubject $subject) {
            foreach ($subject->lessonFiles()->withTrashed()->get() as $file) {
                $file->forceDelete();
            }
            StorageCleaner::deleteThumbnail($subject->thumbnail_path, $subject);
        });

        static::updated(function (FreeTrialSubject $subject) {
            if ($subject->wasChanged('thumbnail_path')) {
                $oldThumb = $subject->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $subject->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $subject);
                }
            }
        });
    }

    public function freeTrialGrade(): BelongsTo {
        return $this->belongsTo(FreeTrialGrade::class, 'free_trial_grade_id');
    }

    public function lessonFiles(): HasMany {
        return $this->hasMany(FreeTrialLessonFile::class);
    }
}
