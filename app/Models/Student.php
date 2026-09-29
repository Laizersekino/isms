<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    protected $fillable = [
        'admission_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'status',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            ParentModel::class,
            'student_parents',
            'student_id',
            'parent_id'
        )->withPivot('relationship', 'is_primary');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class);
    }
}