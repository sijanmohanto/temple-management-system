<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ManualIncome extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_ROCKET = 'rocket';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'income_number',
        'income_date',
        'deposit_account_id',
        'income_account_id',
        'amount',
        'payer_name',
        'payment_method',
        'reference_number',
        'description',
        'event_id',
        'status',
        'created_by',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'income_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Asset deposit account.
     */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'deposit_account_id');
    }

    /**
     * Income category account.
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'income_account_id');
    }

    /**
     * Optional event dimension.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Associated posted journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Concurrency-safe unique income code generator (INC-YYYY-0001).
     */
    public static function generateIncomeNumber(Carbon|string|null $date = null): string
    {
        $dateObj = is_string($date) ? Carbon::parse($date) : ($date ?? now());
        $year = $dateObj->year;
        $prefix = "INC-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('income_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('income_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/INC-\d{4}-(\d+)/', $last->income_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('income_number', $code)->exists()) {
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

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['income_account_id'])) {
            $query->where('income_account_id', $filters['income_account_id']);
        }

        if (! empty($filters['deposit_account_id'])) {
            $query->where('deposit_account_id', $filters['deposit_account_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('income_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('income_date', '<=', $filters['to_date']);
        }

        return $query;
    }
}
