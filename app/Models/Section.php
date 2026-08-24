<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['grade_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    protected static function booted(): void
    {
        static::forceDeleting(function (Section $section) {
            foreach ($section->subjects()->withTrashed()->get() as $subject) {
                $subject->forceDelete();
            }
            StorageCleaner::deleteThumbnail($section->thumbnail_path, $section);
        });

        static::updated(function (Section $section) {
            if ($section->wasChanged('thumbnail_path')) {
                $oldThumb = $section->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $section->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $section);
                }
            }
        });
    }

    public function grade(): BelongsTo { 
        return $this->belongsTo(Grade::class); 
    }
    
    public function subjects(): HasMany { 
        return $this->hasMany(Subject::class); 
    }
}
