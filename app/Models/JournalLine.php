<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasFactory;

    public const TYPE_DEBIT = 'debit';

    public const TYPE_CREDIT = 'credit';

    protected $fillable = [
        'journal_entry_id',
        'financial_account_id',
        'entry_type',
        'amount',
        'event_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Parent journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Target financial account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /**
     * Optional event dimension.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Check if debit.
     */
    public function isDebit(): bool
    {
        return $this->entry_type === self::TYPE_DEBIT;
    }

    /**
     * Check if credit.
     */
    public function isCredit(): bool
    {
        return $this->entry_type === self::TYPE_CREDIT;
    }
}
