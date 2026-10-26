<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CollectionPayment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending_verification';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVERSED = 'reversed';

    public const TYPE_EVENT_PLEDGE = 'event_pledge';

    public const TYPE_EVENT_DONATION = 'event_donation';

    public const TYPE_MONTHLY_CONTRIBUTION = 'monthly_contribution';

    public const TYPE_GENERAL_DONATION = 'general_donation';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_ROCKET = 'rocket';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'payment_code',
        'payer_person_id',
        'payer_family_id',
        'payer_name_override',
        'payer_phone_override',
        'collection_type',
        'event_id',
        'payment_date',
        'payment_year',
        'amount',
        'payment_method',
        'transaction_reference',
        'bank_name',
        'cheque_number',
        'payment_notes',
        'status',
        'submitted_by',
        'verified_by',
        'verified_at',
        'verification_note',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'is_direct_cash_entry',
        'finance_posted_at',
        'finance_post_reference',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'reversed_at' => 'datetime',
            'finance_posted_at' => 'datetime',
            'is_direct_cash_entry' => 'boolean',
        ];
    }

    /**
     * Payer person.
     */
    public function payerPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'payer_person_id');
    }

    /**
     * Payer family.
     */
    public function payerFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'payer_family_id');
    }

    /**
     * Associated event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * User who submitted the payment.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * User who verified the payment.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * User who rejected the payment.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * User who reversed the payment.
     */
    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Allocations created from this payment.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Receipt generated upon verification.
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * Concurrency-safe unique payment code generator (PAY-YYYY-0001).
     */
    public static function generatePaymentCode(int $year): string
    {
        $prefix = "PAY-{$year}-";
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($year, $prefix) {
                $last = self::withTrashed()
                    ->where('payment_year', $year)
                    ->lockForUpdate()
                    ->orderByDesc('payment_code')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/PAY-\d{4}-(\d+)/', $last->payment_code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('payment_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Resolved display name of the payer.
     */
    public function getPayerDisplayNameAttribute(): string
    {
        if ($this->payerPerson) {
            return $this->payerPerson->name_en;
        }

        if ($this->payerFamily) {
            return $this->payerFamily->family_name.' (Family)';
        }

        if ($this->payer_name_override) {
            return $this->payer_name_override;
        }

        return 'Anonymous / General Devotee';
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function isFinancePosted(): bool
    {
        return ! empty($this->finance_posted_at);
    }

    /**
     * Filter scope.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['collection_type'])) {
            $query->where('collection_type', $filters['collection_type']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['payment_year'])) {
            $query->where('payment_year', $filters['payment_year']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('payment_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('payment_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Search scope.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $clean = trim($term);

        return $query->where(function (Builder $q) use ($clean) {
            $q->where('payment_code', 'like', "%{$clean}%")
                ->orWhere('transaction_reference', 'like', "%{$clean}%")
                ->orWhere('payer_name_override', 'like', "%{$clean}%")
                ->orWhere('payer_phone_override', 'like', "%{$clean}%")
                ->orWhereHas('payerPerson', function (Builder $pq) use ($clean) {
                    $pq->where('name_en', 'like', "%{$clean}%")
                        ->orWhere('name_bn', 'like', "%{$clean}%")
                        ->orWhere('phone', 'like', "%{$clean}%")
                        ->orWhere('person_code', 'like', "%{$clean}%");
                })
                ->orWhereHas('payerFamily', function (Builder $fq) use ($clean) {
                    $fq->where('family_name', 'like', "%{$clean}%")
                        ->orWhere('family_code', 'like', "%{$clean}%");
                })
                ->orWhereHas('receipt', function (Builder $rq) use ($clean) {
                    $rq->where('receipt_number', 'like', "%{$clean}%");
                });
        });
    }
}
