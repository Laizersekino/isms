<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCopy extends Model
{
    protected $fillable = [
        'book_id',
        'copy_number',
        'barcode',
        'condition',
        'status',
        'acquisition_date',
        'purchase_price',
        'remarks',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class);
    }
}