<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model {
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'educational_stage_id',
        'grade_id',
        'section_id',
        'subject_id',
        'description',
        'expiry_date',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'expiry_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function educationalStage(): BelongsTo {
        return $this->belongsTo(EducationalStage::class, 'educational_stage_id');
    }

    public function grade(): BelongsTo {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function section(): BelongsTo {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
