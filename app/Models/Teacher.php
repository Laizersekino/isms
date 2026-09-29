<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Teacher extends Model
{
    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'qualification',
        'specialization',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'teacher_subjects',
            'teacher_id',
            'subject_id'
        );
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class);
    }
    public function assignments(): HasMany
{
    return $this->hasMany(TeacherAssignment::class);
}
public function user(): HasOne
{
    return $this->hasOne(User::class);
}
}