<?php
namespace App\Models;

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

    public function lesson(): BelongsTo { 
        return $this->belongsTo(Lesson::class); 
    }
    
    public function progress(): HasMany { 
        return $this->hasMany(UserProgress::class); 
    }
}
