<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'sale_id',
        'transaction_type',
        'points_change',
        'previous_points',
        'new_points',
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'points_change' => 'integer',
        'previous_points' => 'integer',
        'new_points' => 'integer',
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the customer for this transaction
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the sale for this transaction (if applicable)
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Scope for specific transaction types
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for transactions in a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
