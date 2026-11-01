<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'purchase_request_item_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'total_price',
        'financial_account_id',
        'budget_item_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->line_total === null || $item->line_total === '') {
                $item->line_total = round((float) $item->quantity * (float) $item->unit_price, 2);
            }
        });
    }

    public function getItemDescriptionAttribute(): string
    {
        return $this->description ?? '';
    }

    public function setItemDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value;
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->line_total;
    }

    public function setTotalPriceAttribute($value): void
    {
        $this->attributes['line_total'] = $value;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'purchase_request_item_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'budget_item_id');
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(VendorBillItem::class, 'purchase_order_item_id');
    }

    /**
     * Quantity already billed across active/approved bills.
     */
    public function getBilledQuantityAttribute(): float
    {
        return (float) $this->billItems()
            ->whereHas('vendorBill', function ($q) {
                $q->whereNotIn('status', [VendorBill::STATUS_REJECTED, VendorBill::STATUS_CANCELLED]);
            })
            ->sum('quantity');
    }

    /**
     * Remaining unbilled quantity on this purchase order line item.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0.00, round((float) $this->quantity - $this->billed_quantity, 2));
    }

    /**
     * Check if this item is completely fulfilled/billed.
     */
    public function isFullyBilled(): bool
    {
        return $this->remaining_quantity <= 0.00;
    }
}
