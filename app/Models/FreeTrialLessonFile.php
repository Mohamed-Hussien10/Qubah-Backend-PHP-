<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeTrialLessonFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'free_trial_subject_id', 
        'title', 
        'type', 
        'file_path', 
        'thumbnail_path', 
        'metadata', 
        'order', 
        'is_active'
    ];
    
    protected $appends = ['thumbnail_url', 'file_url'];

    public function getThumbnailUrlAttribute() {
        return $this->thumbnail_path ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . $this->thumbnail_path)) : null;
    }

    public function getFileUrlAttribute() {
        if (!$this->file_path) return null;
        if (str_starts_with($this->file_path, 'http')) return $this->file_path;
        $path = str_starts_with($this->file_path, 'storage/') ? substr($this->file_path, 8) : $this->file_path;
        return url('storage/' . $path);
    }

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function freeTrialSubject(): BelongsTo {
        return $this->belongsTo(FreeTrialSubject::class);
    }
}
