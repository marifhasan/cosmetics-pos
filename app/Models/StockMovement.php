<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_variant_id',
        'movement_type',
        'reference_id',
        'quantity_change',
        'previous_quantity',
        'new_quantity',
        'notes',
        'movement_date',
        'user_id',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'movement_date' => 'datetime',
    ];

    /**
     * Get the product variant for this movement
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the user for this movement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for specific movement types
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope for movements in a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }
}
