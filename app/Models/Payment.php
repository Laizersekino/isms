<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    protected $fillable = [
        'student_fee_id',
        'student_id',
        'amount',
        'payment_method',
        'reference_number',
        'receipt_number',
        'paid_date',
        'recorded_by',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_date' => 'date',
            'reversed_at' => 'datetime',
        ];
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeReversed(Builder $query): Builder
    {
        return $query->where('status', 'reversed');
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public static function generateReceiptNumber(): string
    {
        $period = now()->format('Ym');

        return DB::transaction(function () use ($period): string {
            $timestamp = now();
            DB::table('payment_receipt_counters')->insertOrIgnore([
                'period' => $period,
                'last_number' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $counter = DB::table('payment_receipt_counters')
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            $nextNumber = $counter->last_number + 1;

            DB::table('payment_receipt_counters')
                ->where('period', $period)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => $timestamp,
                ]);

            return sprintf('RCP-%s-%05d', $period, $nextNumber);
        });
    }
}
