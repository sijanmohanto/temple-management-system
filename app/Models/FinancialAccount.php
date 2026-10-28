<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const BALANCE_DEBIT = 'debit';

    public const BALANCE_CREDIT = 'credit';

    // Well-known stable account codes
    public const CODE_CASH = '1010';

    public const CODE_BANK = '1020';

    public const CODE_MFS = '1030';

    public const CODE_AP = '2010';

    public const CODE_GENERAL_FUND = '3010';

    public const CODE_OPENING_EQUITY = '3020';

    public const CODE_INCOME_GENERAL = '4010';

    public const CODE_INCOME_EVENT = '4020';

    public const CODE_INCOME_COMMITTEE = '4030';

    public const CODE_INCOME_MISC = '4040';

    public const CODE_EXPENSE_PUJA = '5010';

    public const CODE_EXPENSE_MAINTENANCE = '5020';

    public const CODE_EXPENSE_UTILITIES = '5030';

    public const CODE_EXPENSE_DECORATION = '5040';

    public const CODE_EXPENSE_BHOG = '5050';

    public const CODE_EXPENSE_PRIEST = '5060';

    public const CODE_EXPENSE_BANK_CHARGES = '5070';

    public const CODE_EXPENSE_MISC = '5080';

    public const CODE_EXP_PUJA = self::CODE_EXPENSE_PUJA;

    public const CODE_EXP_BANK_CHARGES = self::CODE_EXPENSE_BANK_CHARGES;

    /**
     * Create a new Eloquent query builder for the model that transparently maps 'code' to 'account_code'.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column)) {
                    if ($column === 'code' || str_ends_with($column, '.code')) {
                        $column = str_replace('.code', '.account_code', $column === 'code' ? 'account_code' : $column);
                    }
                }

                return parent::where($column, $operator, $value, $boolean);
            }

            public function orderBy($column, $direction = 'asc')
            {
                if (is_string($column)) {
                    if ($column === 'code' || str_ends_with($column, '.code')) {
                        $column = str_replace('.code', '.account_code', $column === 'code' ? 'account_code' : $column);
                    }
                }

                return parent::orderBy($column, $direction);
            }
        };
    }

    protected $fillable = [
        'account_code',
        'account_name_en',
        'account_name_bn',
        'code',
        'name_en',
        'name_bn',
        'account_type',
        'normal_balance',
        'parent_id',
        'is_system',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['account_code'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['account_code'] = $value;
    }

    public function getNameEnAttribute(): ?string
    {
        return $this->attributes['account_name_en'] ?? null;
    }

    public function setNameEnAttribute($value): void
    {
        $this->attributes['account_name_en'] = $value;
    }

    public function getNameBnAttribute(): ?string
    {
        return $this->attributes['account_name_bn'] ?? null;
    }

    public function setNameBnAttribute($value): void
    {
        $this->attributes['account_name_bn'] = $value;
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Parent account if hierarchical.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Journal lines posted to this account.
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * Localized name accessor.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->account_name_bn)) {
            return $this->account_name_bn;
        }

        return $this->account_name_en;
    }

    /**
     * Display label with code: "1010 - Cash in Hand".
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->account_code} — {$this->name}";
    }

    /**
     * Calculate current net balance of this account across all posted journal entries.
     */
    public function getBalanceAttribute(): float
    {
        return $this->calculateBalance();
    }

    /**
     * Calculate current net balance across posted entries, optionally filtered by date or event.
     */
    public function calculateBalance(?string $asOfDate = null, ?int $eventId = null): float
    {
        $query = $this->journalLines()
            ->whereHas('journalEntry', function (Builder $q) use ($asOfDate) {
                $q->where('status', JournalEntry::STATUS_POSTED);
                if ($asOfDate) {
                    $q->whereDate('entry_date', '<=', $asOfDate);
                }
            });

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $debits = (float) (clone $query)->where('entry_type', 'debit')->sum('amount');
        $credits = (float) (clone $query)->where('entry_type', 'credit')->sum('amount');

        if ($this->normal_balance === self::BALANCE_DEBIT) {
            return round($debits - $credits, 2);
        }

        return round($credits - $debits, 2);
    }

    /**
     * Safe account code lookup helper.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('account_code', $code)->first();
    }

    /**
     * Scope active accounts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope payment accounts (Cash, Bank, MFS assets).
     */
    public function scopePaymentAccounts(Builder $query): Builder
    {
        return $query->where('account_type', self::TYPE_ASSET)->where('is_active', true);
    }

    /**
     * Scope expense accounts.
     */
    public function scopeExpenseAccounts(Builder $query): Builder
    {
        return $query->where('account_type', self::TYPE_EXPENSE)->where('is_active', true);
    }

    /**
     * Scope income accounts.
     */
    public function scopeIncomeAccounts(Builder $query): Builder
    {
        return $query->where('account_type', self::TYPE_INCOME)->where('is_active', true);
    }
}
