<?php

namespace App\Services;

use App\Models\BookBorrowing;
use Carbon\CarbonImmutable;

class LibraryFineCalculator
{
    public function amountFor(BookBorrowing $borrowing, CarbonImmutable $returnedAt): string
    {
        $dueDate = CarbonImmutable::instance($borrowing->due_date)->startOfDay();
        $returnDate = $returnedAt->startOfDay();
        $daysLate = max(0, (int) $dueDate->diffInDays($returnDate, false));
        $finePerDay = (float) config('library.fine_per_day', 500);

        return number_format($daysLate * $finePerDay, 2, '.', '');
    }
}
