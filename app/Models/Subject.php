<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['section_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    protected static function booted(): void
    {
        static::forceDeleting(function (Subject $subject) {
            foreach ($subject->units()->withTrashed()->get() as $unit) {
                $unit->forceDelete();
            }
            StorageCleaner::deleteThumbnail($subject->thumbnail_path, $subject);
        });

        static::updated(function (Subject $subject) {
            if ($subject->wasChanged('thumbnail_path')) {
                $oldThumb = $subject->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $subject->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $subject);
                }
            }
        });
    }

    public function section(): BelongsTo { 
        return $this->belongsTo(Section::class); 
    }
    
    public function units(): HasMany { 
        return $this->hasMany(Unit::class); 
    }
}
