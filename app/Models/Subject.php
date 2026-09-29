<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Subject extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            Teacher::class,
            'teacher_subjects',
            'subject_id',
            'teacher_id'
        );
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassRoom::class,
            'class_subjects',
            'subject_id',
            'class_id'
        );
    }
    public function examSubjects(): HasMany
{
    return $this->hasMany(ExamSubject::class);
}
}