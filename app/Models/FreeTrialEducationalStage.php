<?php
namespace App\Models;

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
    
    public function grades(): HasMany { 
        return $this->hasMany(FreeTrialGrade::class); 
    }
}
