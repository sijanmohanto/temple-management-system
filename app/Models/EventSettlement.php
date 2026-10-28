<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EventSettlement extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'settlement_number',
        'event_id',
        'settlement_date',
        'total_collections',
        'total_expenses',
        'surplus_amount',
        'journal_entry_id',
        'settled_by',
        'destination_account_id',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'total_collections' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'surplus_amount' => 'decimal:2',
    ];

    /**
     * Parent event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * General ledger journal voucher created for the surplus transfer.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Authorized user who finalized the settlement.
     */
    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * Destination fund account (typically General Temple Fund 3010).
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id');
    }

    /**
     * Check if settlement is active / completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Generate unique settlement number (STL-YYYY-0001).
     */
    public static function generateSettlementNumber(?string $date = null): string
    {
        $year = $date ? Carbon::parse($date)->year : now()->year;

        return DB::transaction(function () use ($year) {
            $prefix = "STL-{$year}-";

            $latest = self::where('settlement_number', 'like', "{$prefix}%")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $nextSequence = 1;
            if ($latest && preg_match('/STL-\d{4}-(\d+)/', $latest->settlement_number, $matches)) {
                $nextSequence = (int) $matches[1] + 1;
            }

            return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
