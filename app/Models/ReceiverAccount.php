<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ReceiverAccount extends Model
{
    use HasFactory, SoftDeletes;

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_ROCKET = 'rocket';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_OTHER = 'other';

    public const ALLOWED_METHODS = [
        self::METHOD_BKASH,
        self::METHOD_NAGAD,
        self::METHOD_ROCKET,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_OTHER,
    ];

    public const TYPE_MERCHANT = 'merchant';

    public const TYPE_PERSONAL = 'personal';

    public const TYPE_CURRENT = 'current';

    public const TYPE_SAVINGS = 'savings';

    public const TYPE_AGENT = 'agent';

    public const ALLOWED_TYPES = [
        self::TYPE_MERCHANT,
        self::TYPE_PERSONAL,
        self::TYPE_CURRENT,
        self::TYPE_SAVINGS,
        self::TYPE_AGENT,
    ];

    protected $fillable = [
        'payment_method',
        'account_name',
        'account_number',
        'account_type',
        'bank_name',
        'branch_name',
        'routing_number',
        'swift_code',
        'qr_code_image_path',
        'instructions_en',
        'instructions_bn',
        'is_active',
        'is_verified',
        'verified_at',
        'verified_by',
        'display_order',
        'financial_account_id',
        'event_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'display_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function isPublicReady(): bool
    {
        return $this->is_active && $this->is_verified;
    }

    public function getInstructionsAttribute(): ?string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->instructions_bn)) {
            return $this->instructions_bn;
        }

        return $this->instructions_en;
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        if (! $this->qr_code_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->qr_code_image_path);
    }

    public function getMethodNameAttribute(): string
    {
        $key = 'portal.payment_methods.'.$this->payment_method;
        $label = __($key);

        return $label !== $key ? $label : ucwords(str_replace('_', ' ', $this->payment_method));
    }

    public function getTypeNameAttribute(): string
    {
        $key = 'portal.account_types.'.$this->account_type;
        $label = __($key);

        return $label !== $key ? $label : ucwords($this->account_type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePublicReady($query)
    {
        return $query->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('display_order');
    }

    public function scopeMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }
}
