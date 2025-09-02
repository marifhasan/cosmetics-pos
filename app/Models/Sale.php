<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'sale_number',
        'customer_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'points_earned',
        'payment_method',
        'payment_status',
        'notes',
        'sale_date',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'points_earned' => 'integer',
        'sale_date' => 'datetime',
    ];

    /**
     * Get the customer for this sale
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user (cashier) for this sale
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all sale items for this sale
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get all loyalty point transactions for this sale
     */
    public function loyaltyPointTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    /**
     * Generate unique sale number
     */
    public static function generateSaleNumber(): string
    {
        $prefix = 'SALE';
        $date = now()->format('Ymd');
        $lastSale = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastSale ? (int)substr($lastSale->sale_number, -4) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate sale number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = self::generateSaleNumber();
            }
        });
    }
}
