<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryFine extends Model
{
    protected $fillable = [
        'book_borrowing_id',
        'amount',
        'reason',
        'status',
        'issued_date',
        'paid_date',
        'recorded_by',
        'paid_by',
        'waived_by',
        'waived_date',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_date' => 'date',
        'paid_date' => 'date',
        'waived_date' => 'date',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(
            BookBorrowing::class,
            'book_borrowing_id'
        );
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
