<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['unit_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    public function unit(): BelongsTo { 
        return $this->belongsTo(Unit::class); 
    }
    
    public function lessonFiles(): HasMany { 
        return $this->hasMany(LessonFile::class); 
    }
}
