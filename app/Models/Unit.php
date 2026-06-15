<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['subject_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    public function subject(): BelongsTo { 
        return $this->belongsTo(Subject::class); 
    }
    
    public function lessons(): HasMany { 
        return $this->hasMany(Lesson::class); 
    }
}
