<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['educational_stage_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    protected static function booted(): void
    {
        static::forceDeleting(function (Grade $grade) {
            foreach ($grade->sections()->withTrashed()->get() as $section) {
                $section->forceDelete();
            }
            StorageCleaner::deleteThumbnail($grade->thumbnail_path, $grade);
        });

        static::updated(function (Grade $grade) {
            if ($grade->wasChanged('thumbnail_path')) {
                $oldThumb = $grade->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $grade->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $grade);
                }
            }
        });
    }

    public function educationalStage(): BelongsTo { 
        return $this->belongsTo(EducationalStage::class); 
    }
    
    public function sections(): HasMany { 
        return $this->hasMany(Section::class); 
    }
}
