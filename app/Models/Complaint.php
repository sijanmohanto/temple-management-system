<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CLOSED = 'closed';

    public const ALLOWED_STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_RESOLVED,
        self::STATUS_REJECTED,
        self::STATUS_CLOSED,
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const ALLOWED_PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    public const CATEGORY_PAYMENT_ISSUE = 'payment_issue';

    public const CATEGORY_EVENT_MANAGEMENT = 'event_management';

    public const CATEGORY_PRASADAM_SERVICE = 'prasadam_service';

    public const CATEGORY_GENERAL_INQUIRY = 'general_inquiry';

    public const CATEGORY_SUGGESTION = 'suggestion';

    public const CATEGORY_OTHER = 'other';

    public const ALLOWED_CATEGORIES = [
        self::CATEGORY_PAYMENT_ISSUE,
        self::CATEGORY_EVENT_MANAGEMENT,
        self::CATEGORY_PRASADAM_SERVICE,
        self::CATEGORY_GENERAL_INQUIRY,
        self::CATEGORY_SUGGESTION,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'tracking_number',
        'complainant_name',
        'complainant_phone',
        'complainant_email',
        'person_id',
        'event_id',
        'collection_payment_id',
        'category',
        'subject',
        'description',
        'attachment_path',
        'status',
        'priority',
        'assigned_to',
        'admin_response',
        'internal_notes',
        'resolved_at',
        'resolved_by',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Generate concurrency-safe unique tracking number (CMP-YYYY-0001).
     */
    public static function generateTrackingNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "CMP-{$year}-";
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('tracking_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('tracking_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/CMP-\d{4}-(\d+)/', $last->tracking_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('tracking_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CollectionPayment::class, 'collection_payment_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }

    public function getCategoryNameAttribute(): string
    {
        $key = 'portal.complaint_categories.'.$this->category;
        $label = __($key);

        return $label !== $key ? $label : ucwords(str_replace('_', ' ', $this->category));
    }

    public function getPriorityNameAttribute(): string
    {
        $key = 'portal.complaint_priorities.'.$this->priority;
        $label = __($key);

        return $label !== $key ? $label : ucwords($this->priority);
    }

    public function getStatusNameAttribute(): string
    {
        $key = 'portal.complaint_statuses.'.$this->status;
        $label = __($key);

        return $label !== $key ? $label : ucwords(str_replace('_', ' ', $this->status));
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}
