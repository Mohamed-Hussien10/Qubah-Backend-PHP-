<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model {
    protected $table = 'user_progress';
    protected $fillable = ['user_id', 'lesson_file_id', 'status', 'time_spent', 'score'];
    
    public function user(): BelongsTo { 
        return $this->belongsTo(User::class); 
    }
    
    public function lessonFile(): BelongsTo { 
        return $this->belongsTo(LessonFile::class); 
    }
}
