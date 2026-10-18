<?php

namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeTrialLessonFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'free_trial_subject_id', 
        'title', 
        'type', 
        'file_path', 
        'thumbnail_path', 
        'metadata', 
        'order', 
        'is_active'
    ];
    
    protected $appends = ['thumbnail_url', 'file_url'];

    public function getThumbnailUrlAttribute() {
        return $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null;
    }

    public function getFileUrlAttribute() {
        if (!$this->file_path) return null;
        if (str_starts_with($this->file_path, 'http')) return $this->file_path;
        $path = str_starts_with($this->file_path, 'storage/') ? substr($this->file_path, 8) : $this->file_path;
        return url('storage/' . $path);
    }

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (FreeTrialLessonFile $file) {
            StorageCleaner::deleteFile($file->file_path, $file);
            StorageCleaner::deleteThumbnail($file->thumbnail_path, $file);
        });

        static::updated(function (FreeTrialLessonFile $file) {
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

    public function freeTrialSubject(): BelongsTo {
        return $this->belongsTo(FreeTrialSubject::class);
    }
}
