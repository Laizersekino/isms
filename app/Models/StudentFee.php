<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'fee_structure_item_id',
        'amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'due_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeStructureItem(): BelongsTo
    {
        return $this->belongsTo(FeeStructureItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereDate('due_date', '<', today()->toDateString())
            ->where('status', '!=', 'paid');
    }

    public function recalculateBalance(): void
    {
        $amount = (int) round((float) $this->amount * 100);
        $paidAmount = (int) round((float) $this->paid_amount * 100);
        $balance = ($amount - $paidAmount) / 100;

        $status = match (true) {
            $paidAmount === 0 => 'unpaid',
            $paidAmount < $amount => 'partially_paid',
            default => 'paid',
        };

        $this->update([
            'balance' => number_format($balance, 2, '.', ''),
            'status' => $status,
        ]);
    }

    public function isFullyPaid(): bool
    {
        $amount = (int) round((float) $this->amount * 100);
        $paidAmount = (int) round((float) $this->paid_amount * 100);

        return $paidAmount >= $amount;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(today())
            && $this->status !== 'paid';
    }
}
