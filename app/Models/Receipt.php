<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Receipt extends Model
{
    use HasFactory;

    public const STATUS_VALID = 'valid';

    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'receipt_number',
        'collection_payment_id',
        'receipt_year',
        'issued_at',
        'issued_by',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * Associated collection payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(CollectionPayment::class, 'collection_payment_id');
    }

    /**
     * User who issued the receipt.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * User who voided the receipt.
     */
    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Concurrency-safe unique receipt number generator (RCP-YYYY-0001).
     */
    public static function generateReceiptNumber(int $year): string
    {
        $prefix = "RCP-{$year}-";
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $number = DB::transaction(function () use ($year, $prefix) {
                $last = self::where('receipt_year', $year)
                    ->lockForUpdate()
                    ->orderByDesc('receipt_number')
                    ->first();

                $next = 1;
                if ($last && preg_match('/RCP-\d{4}-(\d+)/', $last->receipt_number, $matches)) {
                    $next = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            });

            if (! self::where('receipt_number', $number)->exists()) {
                return $number;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }
}
