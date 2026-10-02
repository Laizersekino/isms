<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function currentBorrowing(): HasOne
    {
        return $this->hasOne(BookBorrowing::class)
            ->where('status', 'borrowed')
            ->whereNull('returned_date');
    }

    public function scopeAvailableForBorrowing(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }
}
