<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOtp extends Model
{
    use HasFactory;

    public const PURPOSE_PROFILE_UPDATE = 'profile_update';

    public const PURPOSE_PASSWORD_CHANGE = 'password_change';

    public const PURPOSE_EMAIL_CHANGE = 'email_change';

    protected $fillable = [
        'user_id',
        'email',
        'otp_hash',
        'purpose',
        'expires_at',
        'used_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Associated user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP was already used.
     */
    public function isUsed(): bool
    {
        return ! is_null($this->used_at);
    }

    /**
     * Check if OTP is still valid.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed() && $this->attempts < 5;
    }
}
