<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['educational_stage_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    public function educationalStage(): BelongsTo { 
        return $this->belongsTo(EducationalStage::class); 
    }
    
    public function sections(): HasMany { 
        return $this->hasMany(Section::class); 
    }
}
