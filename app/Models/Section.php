<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['grade_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    public function grade(): BelongsTo { 
        return $this->belongsTo(Grade::class); 
    }
    
    public function subjects(): HasMany { 
        return $this->hasMany(Subject::class); 
    }
}
