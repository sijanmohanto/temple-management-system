<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    // Allowed lifecycle statuses
    public const STATUS_DRAFT = 'draft';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSING = 'closing';

    public const STATUS_CLOSED = 'closed';

    // Closing modes
    public const MODE_CLOSE_AND_LOCK = 'close_and_lock';

    public const MODE_CLOSE_WITH_ADJUSTMENTS = 'close_with_authorized_adjustments';

    protected $fillable = [
        'event_code',
        'name_en',
        'name_bn',
        'event_category_id',
        'event_year',
        'description_en',
        'description_bn',
        'venue',
        'start_date',
        'end_date',
        'collection_start_date',
        'collection_end_date',
        'status',
        'closing_mode',
        'closing_reason',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'collection_start_date' => 'date',
        'collection_end_date' => 'date',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'event_year' => 'integer',
    ];

    /**
     * Category of the event.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * User who created the event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the event.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * User who closed the event.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * User who reopened the event.
     */
    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /**
     * Lifecycle and audit logs for this event.
     */
    public function lifecycleLogs(): HasMany
    {
        return $this->hasMany(EventLifecycleLog::class)->orderByDesc('created_at');
    }

    /**
     * Pledges made for this event.
     */
    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    /**
     * Payments collected for this event.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class);
    }

    /**
     * Localized name attribute.
     */
    public function getNameAttribute(): string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->name_bn)) {
            return $this->name_bn;
        }

        return $this->name_en;
    }

    /**
     * Localized description attribute.
     */
    public function getDescriptionAttribute(): ?string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->description_bn)) {
            return $this->description_bn;
        }

        return $this->description_en;
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isUpcoming(): bool
    {
        return $this->status === self::STATUS_UPCOMING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosing(): bool
    {
        return $this->status === self::STATUS_CLOSING;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isLocked(): bool
    {
        return $this->isClosed() && $this->closing_mode === self::MODE_CLOSE_AND_LOCK;
    }

    public function allowsAuthorizedAdjustments(): bool
    {
        return $this->isClosed() && $this->closing_mode === self::MODE_CLOSE_WITH_ADJUSTMENTS;
    }

    /**
     * Check if event has a collection period configured.
     */
    public function hasCollectionPeriod(): bool
    {
        return ! empty($this->collection_start_date) && ! empty($this->collection_end_date);
    }

    /**
     * Scopes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UPCOMING);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeYear(Builder $query, ?int $year): Builder
    {
        if ($year) {
            return $query->where('event_year', $year);
        }

        return $query;
    }

    public function scopeCategory(Builder $query, ?int $catId): Builder
    {
        if ($catId) {
            return $query->where('event_category_id', $catId);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $clean = trim($term);

        return $query->where(function (Builder $q) use ($clean) {
            $q->where('event_code', 'like', "%{$clean}%")
                ->orWhere('name_en', 'like', "%{$clean}%")
                ->orWhere('name_bn', 'like', "%{$clean}%")
                ->orWhere('venue', 'like', "%{$clean}%")
                ->orWhereHas('category', function (Builder $cq) use ($clean) {
                    $cq->where('name_en', 'like', "%{$clean}%")
                        ->orWhere('name_bn', 'like', "%{$clean}%");
                });
        });
    }

    /**
     * Filter query by search, category, year, status.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $searchTerm = is_array($filters['search']) ? ($filters['search']['value'] ?? '') : $filters['search'];
            if (! empty($searchTerm)) {
                $query->search((string) $searchTerm);
            }
        }

        if (! empty($filters['event_category_id'])) {
            $query->where('event_category_id', $filters['event_category_id']);
        }

        if (! empty($filters['event_year'])) {
            $query->where('event_year', (int) $filters['event_year']);
        }

        if (! empty($filters['status']) && in_array($filters['status'], [
            self::STATUS_DRAFT,
            self::STATUS_UPCOMING,
            self::STATUS_ACTIVE,
            self::STATUS_CLOSING,
            self::STATUS_CLOSED,
        ])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Generate unique event code: EVT-YYYY-0001
     */
    public static function generateEventCode(int $year): string
    {
        return DB::transaction(function () use ($year) {
            $prefix = sprintf('EVT-%d-', $year);

            // Fetch the highest sequence for this year
            $latestEvent = static::withTrashed()
                ->where('event_code', 'like', "{$prefix}%")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $nextSequence = 1;
            if ($latestEvent && preg_match('/^EVT-\d{4}-(\d+)$/', $latestEvent->event_code, $matches)) {
                $nextSequence = (int) $matches[1] + 1;
            }

            return sprintf('EVT-%d-%04d', $year, $nextSequence);
        });
    }
}
