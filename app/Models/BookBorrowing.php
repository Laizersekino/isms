<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookBorrowing extends Model
{
    protected $fillable = [
        'book_copy_id',
        'student_id',
        'teacher_id',
        'issued_by',
        'borrowed_date',
        'due_date',
        'returned_date',
        'status',
        'remarks',
        'renewal_count',
    ];

    protected $casts = [
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'renewal_count' => 'integer',
    ];

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function fines(): HasMany
    {
        return $this->hasMany(LibraryFine::class);
    }
}
