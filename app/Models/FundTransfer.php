<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FundTransfer extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_POSTED = 'posted';

    public const STATUS_COMPLETED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'transfer_number',
        'transfer_date',
        'from_account_id',
        'to_account_id',
        'amount',
        'fee_amount',
        'bank_charge_amount',
        'reference_number',
        'notes',
        'description',
        'journal_entry_id',
        'status',
        'processed_by',
        'processed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'bank_charge_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function getBankChargeAmountAttribute(): float
    {
        return (float) ($this->attributes['fee_amount'] ?? ($this->attributes['bank_charge_amount'] ?? 0.00));
    }

    public function setBankChargeAmountAttribute($value): void
    {
        $this->attributes['fee_amount'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['notes'] ?? ($this->attributes['description'] ?? null);
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['notes'] = $value;
    }

    /**
     * Source asset account.
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    /**
     * Destination asset account.
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }

    /**
     * Associated posted journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * User who created the transfer.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who processed the transfer.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Check if transfer is completed / posted.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_POSTED, 'completed']);
    }

    /**
     * Check if transfer was reversed.
     */
    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    /**
     * Concurrency-safe unique transfer code generator (TRF-YYYY-0001).
     */
    public static function generateTransferNumber(Carbon|string|null $date = null): string
    {
        $dateObj = is_string($date) ? Carbon::parse($date) : ($date ?? now());
        $year = $dateObj->year;
        $prefix = "TRF-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('transfer_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('transfer_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/TRF-\d{4}-(\d+)/', $last->transfer_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('transfer_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Filter scope.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_account_id'])) {
            $query->where('from_account_id', $filters['from_account_id']);
        }

        if (! empty($filters['to_account_id'])) {
            $query->where('to_account_id', $filters['to_account_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('transfer_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('transfer_date', '<=', $filters['to_date']);
        }

        return $query;
    }
}
