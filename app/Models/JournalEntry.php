<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class JournalEntry extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    public const SOURCE_COLLECTION_PAYMENT = 'collection_payment';

    public const SOURCE_MANUAL_INCOME = 'manual_income';

    public const SOURCE_EXPENSE = 'expense';

    public const SOURCE_FUND_TRANSFER = 'fund_transfer';

    public const SOURCE_OPENING_BALANCE = 'opening_balance';

    public const SOURCE_MANUAL = 'manual_journal';

    public const SOURCE_REVERSAL = 'reversal';

    protected $fillable = [
        'journal_number',
        'entry_date',
        'description',
        'source_type',
        'source_id',
        'status',
        'total_debit',
        'total_credit',
        'event_id',
        'created_by',
        'posted_by',
        'posted_at',
        'reversed_journal_id',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
        ];
    }

    /**
     * Associated journal lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * Associated event dimension.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * User who created the entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who authorized/posted the entry.
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Reversal journal entry if this entry was reversed.
     */
    public function reversedJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_journal_id');
    }

    /**
     * The reversal journal entry that reversed this entry (if reversed).
     */
    public function reversedByJournal(): HasOne
    {
        return $this->hasOne(self::class, 'reversed_journal_id');
    }

    /**
     * Concurrency-safe unique journal number generator (JRN-YYYY-0001).
     */
    public static function generateJournalNumber(Carbon|string|null $date = null): string
    {
        $dateObj = is_string($date) ? Carbon::parse($date) : ($date ?? now());
        $year = $dateObj->year;
        $prefix = "JRN-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::where('journal_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('journal_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/JRN-\d{4}-(\d+)/', $last->journal_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::where('journal_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if journal entry is currently posted.
     */
    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Check if journal entry was reversed.
     */
    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    /**
     * Scope posted entries.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    /**
     * Filter scope.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('entry_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('entry_date', '<=', $filters['to_date']);
        }

        return $query;
    }
}
