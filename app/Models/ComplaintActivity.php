<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintActivity extends Model
{
    use HasFactory;

    public const VISIBILITY_DONOR = 'donor';

    public const VISIBILITY_INTERNAL = 'internal';

    public const TYPE_SUBMISSION = 'submission';

    public const TYPE_STATUS_CHANGE = 'status_change';

    public const TYPE_REPLY = 'reply';

    public const TYPE_INTERNAL_NOTE = 'internal_note';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_RESOLUTION = 'resolution';

    public const TYPE_REJECTION = 'rejection';

    public const TYPE_CLOSE = 'close';

    protected $fillable = [
        'complaint_id',
        'actor_id',
        'activity_type',
        'previous_status',
        'new_status',
        'content',
        'visibility',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function isDonorVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_DONOR;
    }

    public function isInternal(): bool
    {
        return $this->visibility === self::VISIBILITY_INTERNAL;
    }
}
