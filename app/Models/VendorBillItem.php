<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_bill_id',
        'purchase_order_item_id',
        'financial_account_id',
        'budget_item_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->line_total === null || $item->line_total === '') {
                $base = round((float) $item->quantity * (float) $item->unit_price, 2);
                $discount = round((float) ($item->discount_amount ?? 0), 2);
                $item->line_total = max(0, round($base - $discount, 2));
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
        return (float) ($this->line_total ?? 0);
    }

    public function setTotalPriceAttribute($value): void
    {
        $this->attributes['line_total'] = $value;
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class, 'vendor_bill_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'budget_item_id');
    }
}
