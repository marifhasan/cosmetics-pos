<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'variant_name',
        'sku',
        'cost_price',
        'selling_price',
        'stock_quantity',
        'min_stock_level',
        'barcode',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'stock_status',
        'current_stock'
    ];

    /**
     * Get the product for this variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all sale items for this variant
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get all purchase items for this variant
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get all stock movements for this variant
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get stock status accessor
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($this->stock_quantity <= $this->min_stock_level) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    /**
     * Get current stock accessor (alias for stock_quantity)
     */
    public function getCurrentStockAttribute(): int
    {
        return $this->stock_quantity;
    }

    /**
     * Scope for low stock alerts
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }

    /**
     * Scope for out of stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    /**
     * Scope to get only active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Update stock and create stock movement record
     */
    public function updateStock(int $quantityChange, string $movementType, ?int $referenceId = null, ?int $userId = null, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($quantityChange, $movementType, $referenceId, $userId, $notes) {
            $previousQuantity = $this->stock_quantity;
            $newQuantity = $previousQuantity + $quantityChange;
            
            // Prevent negative stock
            if ($newQuantity < 0) {
                throw new \Exception('Insufficient stock. Available: ' . $previousQuantity);
            }
            
            // Update stock quantity
            $this->update(['stock_quantity' => $newQuantity]);
            
            // Create stock movement record
            StockMovement::create([
                'product_variant_id' => $this->id,
                'movement_type' => $movementType,
                'reference_id' => $referenceId,
                'quantity_change' => $quantityChange,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'notes' => $notes,
                'movement_date' => now(),
                'user_id' => $userId ?? auth()->id(),
            ]);
            
            return true;
        });
    }
}
