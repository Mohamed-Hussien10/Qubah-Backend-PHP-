<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationalStage extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['title', 'description', 'thumbnail_path', 'background_image_path', 'order', 'is_active'];
    
    public function grades(): HasMany { 
        return $this->hasMany(Grade::class); 
    }
}
