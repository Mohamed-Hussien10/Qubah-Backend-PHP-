<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['section_id', 'title', 'description', 'thumbnail_path', 'order', 'is_active'];
    
    public function section(): BelongsTo { 
        return $this->belongsTo(Section::class); 
    }
    
    public function units(): HasMany { 
        return $this->hasMany(Unit::class); 
    }
}
