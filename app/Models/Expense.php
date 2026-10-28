<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVERSED = 'reversed';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_ROCKET = 'rocket';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'expense_number',
        'expense_date',
        'financial_account_id',
        'expense_account_id',
        'payment_account_id',
        'amount',
        'title_en',
        'title_bn',
        'vendor_name',
        'vendor_phone',
        'invoice_number',
        'reference_number',
        'payee_name',
        'payment_method',
        'notes',
        'description',
        'event_id',
        'status',
        'created_by',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function getExpenseAccountIdAttribute(): ?int
    {
        return $this->attributes['financial_account_id'] ?? null;
    }

    public function setExpenseAccountIdAttribute(?int $value): void
    {
        $this->attributes['financial_account_id'] = $value;
    }

    public function getPayeeNameAttribute(): ?string
    {
        return $this->attributes['vendor_name'] ?? ($this->attributes['payee_name'] ?? null);
    }

    public function setPayeeNameAttribute(?string $value): void
    {
        $this->attributes['vendor_name'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['notes'] ?? ($this->attributes['description'] ?? null);
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['notes'] = $value;
    }

    public function getSubmittedByAttribute(): ?int
    {
        return $this->attributes['created_by'] ?? ($this->attributes['submitted_by'] ?? null);
    }

    public function setSubmittedByAttribute(?int $value): void
    {
        $this->attributes['created_by'] = $value;
    }

    /**
     * Expense category account.
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /**
     * Asset payment account (Cash, Bank, MFS).
     */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'payment_account_id');
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
     * User who submitted/created the voucher.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved and posted the expense.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who rejected the expense.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Concurrency-safe unique expense code generator (EXP-YYYY-0001).
     */
    public static function generateExpenseNumber(Carbon|string|null $date = null): string
    {
        $dateObj = is_string($date) ? Carbon::parse($date) : ($date ?? now());
        $year = $dateObj->year;
        $prefix = "EXP-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('expense_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('expense_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/EXP-\d{4}-(\d+)/', $last->expense_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('expense_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if approved and posted.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if pending review.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Scope approved expenses.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope pending expenses.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
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

        if (! empty($filters['financial_account_id'])) {
            $query->where('financial_account_id', $filters['financial_account_id']);
        }

        if (! empty($filters['payment_account_id'])) {
            $query->where('payment_account_id', $filters['payment_account_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('expense_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('expense_date', '<=', $filters['to_date']);
        }

        return $query;
    }
}
