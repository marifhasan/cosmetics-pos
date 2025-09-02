<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'email',
        'address',
        'birthdate',
        'loyalty_points',
        'is_active',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'loyalty_points' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all sales for this customer
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get all loyalty point transactions for this customer
     */
    public function loyaltyPointTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    /**
     * Scope to get only active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Add loyalty points
     */
    public function addLoyaltyPoints(int $points, ?int $saleId = null, string $description = 'Points earned'): void
    {
        $previousPoints = $this->loyalty_points;
        $newPoints = $previousPoints + $points;
        
        $this->update(['loyalty_points' => $newPoints]);
        
        $this->loyaltyPointTransactions()->create([
            'sale_id' => $saleId,
            'transaction_type' => 'earned',
            'points_change' => $points,
            'previous_points' => $previousPoints,
            'new_points' => $newPoints,
            'description' => $description,
            'transaction_date' => now(),
        ]);
    }

    /**
     * Redeem loyalty points
     */
    public function redeemLoyaltyPoints(int $points, string $description = 'Points redeemed'): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }
        
        $previousPoints = $this->loyalty_points;
        $newPoints = $previousPoints - $points;
        
        $this->update(['loyalty_points' => $newPoints]);
        
        $this->loyaltyPointTransactions()->create([
            'transaction_type' => 'redeemed',
            'points_change' => -$points,
            'previous_points' => $previousPoints,
            'new_points' => $newPoints,
            'description' => $description,
            'transaction_date' => now(),
        ]);
        
        return true;
    }
}
