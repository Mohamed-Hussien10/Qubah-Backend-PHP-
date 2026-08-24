<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonFile extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['lesson_id', 'title', 'type', 'file_path', 'thumbnail_path', 'metadata', 'order', 'is_active'];
    
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (LessonFile $file) {
            StorageCleaner::deleteFile($file->file_path, $file);
            StorageCleaner::deleteThumbnail($file->thumbnail_path, $file);
        });

        static::updated(function (LessonFile $file) {
            if ($file->wasChanged('file_path')) {
                $oldPath = $file->getOriginal('file_path');
                if ($oldPath && $oldPath !== $file->file_path) {
                    StorageCleaner::deleteFile($oldPath, $file);
                }
            }
            if ($file->wasChanged('thumbnail_path')) {
                $oldThumb = $file->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $file->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $file);
                }
            }
        });
    }

    public function lesson(): BelongsTo { 
        return $this->belongsTo(Lesson::class); 
    }
    
    public function progress(): HasMany { 
        return $this->hasMany(UserProgress::class); 
    }
}
