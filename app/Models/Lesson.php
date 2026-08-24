<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['unit_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    protected static function booted(): void
    {
        static::forceDeleting(function (Lesson $lesson) {
            foreach ($lesson->lessonFiles()->withTrashed()->get() as $file) {
                $file->forceDelete();
            }
            StorageCleaner::deleteThumbnail($lesson->thumbnail_path, $lesson);
        });

        static::updated(function (Lesson $lesson) {
            if ($lesson->wasChanged('thumbnail_path')) {
                $oldThumb = $lesson->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $lesson->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $lesson);
                }
            }
        });
    }

    public function unit(): BelongsTo { 
        return $this->belongsTo(Unit::class); 
    }
    
    public function lessonFiles(): HasMany { 
        return $this->hasMany(LessonFile::class); 
    }
}
