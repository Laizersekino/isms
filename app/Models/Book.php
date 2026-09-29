<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'isbn',
        'author',
        'publisher',
        'category',
        'edition',
        'publication_year',
        'description',
        'status',
    ];

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }
}