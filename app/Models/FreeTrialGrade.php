<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FreeTrialGrade extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['free_trial_educational_stage_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    protected $appends = ['thumbnail_url'];

    public function getThumbnailUrlAttribute() {
        return $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null;
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (FreeTrialGrade $grade) {
            foreach ($grade->subjects()->withTrashed()->get() as $subject) {
                $subject->forceDelete();
            }
            StorageCleaner::deleteThumbnail($grade->thumbnail_path, $grade);
        });

        static::updated(function (FreeTrialGrade $grade) {
            if ($grade->wasChanged('thumbnail_path')) {
                $oldThumb = $grade->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $grade->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $grade);
                }
            }
        });
    }
    
    public function educationalStage(): BelongsTo { 
        return $this->belongsTo(FreeTrialEducationalStage::class, 'free_trial_educational_stage_id'); 
    }
    
    public function subjects(): HasMany { 
        return $this->hasMany(FreeTrialSubject::class); 
    }
}
