<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EventDocument extends Model
{
    use HasFactory, SoftDeletes;

    public const PUB_STATUS_DRAFT = 'draft';

    public const PUB_STATUS_PUBLISHED = 'published';

    public const PUB_STATUS_UNPUBLISHED = 'unpublished';

    public const TYPE_FINANCIAL_REPORT = 'financial_report';

    public const TYPE_AUDIT_REPORT = 'audit_report';

    public const TYPE_SUPPORTING_DOCUMENT = 'supporting_document';

    public const TYPE_OTHER = 'other';

    public const ALLOWED_TYPES = [
        self::TYPE_FINANCIAL_REPORT,
        self::TYPE_AUDIT_REPORT,
        self::TYPE_SUPPORTING_DOCUMENT,
        self::TYPE_OTHER,
    ];

    public const CATEGORY_COMPLETE_FINANCIAL_STATEMENT = 'complete_financial_statement';

    public const CATEGORY_INCOME_REPORT = 'income_report';

    public const CATEGORY_EXPENSE_REPORT = 'expense_report';

    public const CATEGORY_EXPENSE_BREAKDOWN = 'expense_breakdown';

    public const CATEGORY_EVENT_SETTLEMENT = 'event_settlement';

    public const CATEGORY_OTHER = 'other';

    public const REPORT_CATEGORIES = [
        self::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
        self::CATEGORY_INCOME_REPORT,
        self::CATEGORY_EXPENSE_REPORT,
        self::CATEGORY_EXPENSE_BREAKDOWN,
        self::CATEGORY_EVENT_SETTLEMENT,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'uuid',
        'event_id',
        'title_en',
        'title_bn',
        'document_type',
        'report_category',
        'financial_year',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'description_en',
        'description_bn',
        'publication_status',
        'published_at',
        'published_by',
        'unpublished_at',
        'unpublished_by',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'file_size' => 'integer',
            'financial_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EventDocument $doc) {
            if (empty($doc->uuid)) {
                $doc->uuid = (string) Str::uuid();
            }
            if (empty($doc->financial_year) && $doc->event) {
                $doc->financial_year = $doc->event->event_year;
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function unpublisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unpublished_by');
    }

    public function isPublished(): bool
    {
        return $this->publication_status === self::PUB_STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->publication_status === self::PUB_STATUS_DRAFT;
    }

    public function isUnpublished(): bool
    {
        return $this->publication_status === self::PUB_STATUS_UNPUBLISHED;
    }

    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->title_bn)) {
            return $this->title_bn;
        }

        return $this->title_en ?? '';
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn' && ! empty($this->description_bn)) {
            return $this->description_bn;
        }

        return $this->description_en;
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function getDocumentTypeNameAttribute(): string
    {
        $key = 'portal.document_types.'.$this->document_type;
        $translated = __($key);

        return $translated !== $key ? $translated : ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function getReportCategoryNameAttribute(): string
    {
        $category = $this->report_category ?? self::CATEGORY_OTHER;
        $key = 'portal.report_categories.'.$category;
        $translated = __($key);

        return $translated !== $key ? $translated : ucwords(str_replace('_', ' ', $category));
    }

    public function scopePublished($query)
    {
        return $query->where('publication_status', self::PUB_STATUS_PUBLISHED);
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('report_category', $category);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('financial_year', $year);
    }
}
