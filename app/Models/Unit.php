<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['subject_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    protected static function booted(): void
    {
        static::forceDeleting(function (Unit $unit) {
            foreach ($unit->lessons()->withTrashed()->get() as $lesson) {
                $lesson->forceDelete();
            }
            StorageCleaner::deleteThumbnail($unit->thumbnail_path, $unit);
        });

        static::updated(function (Unit $unit) {
            if ($unit->wasChanged('thumbnail_path')) {
                $oldThumb = $unit->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $unit->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $unit);
                }
            }
        });
    }

    public function subject(): BelongsTo { 
        return $this->belongsTo(Subject::class); 
    }
    
    public function lessons(): HasMany { 
        return $this->hasMany(Lesson::class); 
    }
}
