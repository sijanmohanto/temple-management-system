<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_PUJA_MATERIALS = 'puja_materials';

    public const TYPE_FOOD_PRASAD = 'food_prasad';

    public const TYPE_DECORATION_LIGHTING = 'decoration_lighting';

    public const TYPE_SOUND_MEDIA = 'sound_media';

    public const TYPE_CONSTRUCTION_MAINTENANCE = 'construction_maintenance';

    public const TYPE_PRINTING_STATIONERY = 'printing_stationery';

    public const TYPE_PRIEST_PERFORMER = 'priest_performer';

    public const TYPE_GENERAL_SUPPLIES = 'general_supplies';

    protected $fillable = [
        'vendor_code',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'vendor_type',
        'tax_id',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Map of available vendor categories.
     *
     * @return array<string, string>
     */
    public static function vendorTypes(): array
    {
        return [
            self::TYPE_PUJA_MATERIALS => __('procurement.type_puja_materials'),
            self::TYPE_FOOD_PRASAD => __('procurement.type_food_prasad'),
            self::TYPE_DECORATION_LIGHTING => __('procurement.type_decoration_lighting'),
            self::TYPE_SOUND_MEDIA => __('procurement.type_sound_media'),
            self::TYPE_CONSTRUCTION_MAINTENANCE => __('procurement.type_construction_maintenance'),
            self::TYPE_PRINTING_STATIONERY => __('procurement.type_printing_stationery'),
            self::TYPE_PRIEST_PERFORMER => __('procurement.type_priest_performer'),
            self::TYPE_GENERAL_SUPPLIES => __('procurement.type_general_supplies'),
        ];
    }

    /**
     * Translated human readable vendor type label.
     */
    public function getVendorTypeLabelAttribute(): string
    {
        $types = self::vendorTypes();

        return $types[$this->vendor_type] ?? ($this->vendor_type ?: __('procurement.type_general_supplies'));
    }

    /**
     * HTML status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_active) {
            return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>'.__('common.active').'</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-dash-circle me-1"></i>'.__('common.inactive').'</span>';
    }

    /**
     * Concurrency-safe unique vendor code generator (VND-0001).
     */
    public static function generateVendorCode(): string
    {
        $prefix = 'VND-';

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('vendor_code', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('vendor_code')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/VND-(\d+)/', $last->vendor_code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('vendor_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) (self::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Created by user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updated by user.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Linked expenses with foreign key vendor_id.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'vendor_id');
    }

    /**
     * Query all expenses linked either directly or historically via phone.
     */
    public function allExpenses(): Builder
    {
        return Expense::where(function (Builder $query) {
            $query->where('vendor_id', $this->id);
            if (! empty($this->phone)) {
                $query->orWhere(function (Builder $sub) {
                    $sub->whereNull('vendor_id')
                        ->where('vendor_phone', $this->phone);
                });
            }
        });
    }

    /**
     * Check if vendor is linked to existing transactions preventing hard delete.
     */
    public function hasProtectedTransactions(): bool
    {
        if ($this->expenses()->exists()) {
            return true;
        }

        if (! empty($this->phone) && Expense::where('vendor_phone', $this->phone)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Scope query to only active vendors.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
