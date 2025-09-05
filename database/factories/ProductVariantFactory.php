<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 5, 100);
        $sellingPrice = $costPrice * fake()->randomFloat(2, 1.1, 2.5); // 10% to 150% markup

        return [
            'product_id' => Product::factory(),
            'variant_name' => fake()->randomElement(['Small', 'Medium', 'Large', '30ml', '50ml', '100ml', 'Regular', 'Matte', 'Glossy']),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{6}'),
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'min_stock_level' => fake()->numberBetween(5, 50),
            'barcode' => fake()->optional(0.8)->ean13(),
            'is_active' => fake()->boolean(95), // 95% chance of being active
        ];
    }

    /**
     * Indicate that the variant should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the variant should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a variant for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Create a variant with low stock.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minStock = fake()->numberBetween(10, 30);
            return [
                'stock_quantity' => fake()->numberBetween(0, $minStock),
                'min_stock_level' => $minStock,
            ];
        });
    }

    /**
     * Create a variant that is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'min_stock_level' => fake()->numberBetween(5, 20),
        ]);
    }

    /**
     * Create a variant with high stock.
     */
    public function inStock(): static
    {
        return $this->state(function (array $attributes) {
            $minStock = fake()->numberBetween(5, 20);
            return [
                'stock_quantity' => fake()->numberBetween($minStock + 10, 500),
                'min_stock_level' => $minStock,
            ];
        });
    }

    /**
     * Create a variant with specific stock levels.
     */
    public function withStock(int $quantity, int $minLevel = null): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $quantity,
            'min_stock_level' => $minLevel ?? fake()->numberBetween(5, 20),
        ]);
    }

    /**
     * Create a variant with a specific SKU.
     */
    public function withSku(string $sku): static
    {
        return $this->state(fn (array $attributes) => [
            'sku' => $sku,
        ]);
    }

    /**
     * Create a variant with prices.
     */
    public function withPrices(float $cost, float $selling): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_price' => $cost,
            'selling_price' => $selling,
        ]);
    }
}
