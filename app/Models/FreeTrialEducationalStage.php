<?php
namespace App\Models;

use App\Services\StorageCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FreeTrialEducationalStage extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['title', 'description', 'thumbnail_path', 'background_image_path', 'order', 'is_active'];
    protected $appends = ['thumbnail_url', 'background_image_url'];

    public function getThumbnailUrlAttribute() {
        return $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null;
    }

    public function getBackgroundImageUrlAttribute() {
        return $this->background_image_path ? (str_starts_with($this->background_image_path, 'http') ? $this->background_image_path : url('storage/' . $this->background_image_path)) : null;
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (FreeTrialEducationalStage $stage) {
            foreach ($stage->grades()->withTrashed()->get() as $grade) {
                $grade->forceDelete();
            }
            StorageCleaner::deleteThumbnail($stage->thumbnail_path, $stage);
            StorageCleaner::deleteThumbnail($stage->background_image_path, $stage);
        });

        static::updated(function (FreeTrialEducationalStage $stage) {
            if ($stage->wasChanged('thumbnail_path')) {
                $oldThumb = $stage->getOriginal('thumbnail_path');
                if ($oldThumb && $oldThumb !== $stage->thumbnail_path) {
                    StorageCleaner::deleteThumbnail($oldThumb, $stage);
                }
            }
            if ($stage->wasChanged('background_image_path')) {
                $oldBg = $stage->getOriginal('background_image_path');
                if ($oldBg && $oldBg !== $stage->background_image_path) {
                    StorageCleaner::deleteThumbnail($oldBg, $stage);
                }
            }
        });
    }
    
    public function grades(): HasMany { 
        return $this->hasMany(FreeTrialGrade::class); 
    }
}
