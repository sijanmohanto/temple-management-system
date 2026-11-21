<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevoteeCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'encrypted_password',
    ];

    /**
     * Associated User account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
