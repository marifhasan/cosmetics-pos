<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'purchase_date',
        'expected_delivery',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'purchase_date' => 'datetime',
        'expected_delivery' => 'date',
    ];

    /**
     * Get the supplier for this purchase
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user for this purchase
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all purchase items for this purchase
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Generate unique purchase number
     */
    public static function generatePurchaseNumber(): string
    {
        $prefix = 'PUR';
        $date = now()->format('Ymd');
        $lastPurchase = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastPurchase ? (int)substr($lastPurchase->purchase_number, -4) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate purchase number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $purchase->purchase_number = self::generatePurchaseNumber();
            }
        });
    }
}
