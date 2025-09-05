<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $taxAmount = $subtotal * fake()->randomFloat(2, 0.05, 0.15); // 5% to 15%
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.1); // Up to 10% discount
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'purchase_number' => fake()->unique()->regexify('PUR[0-9]{8}'),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => fake()->randomElement(['pending', 'received', 'partial', 'cancelled']),
            'notes' => fake()->optional(0.4)->sentence(),
            'expected_delivery_date' => fake()->optional(0.6)->dateTimeBetween('now', '+30 days'),
            'purchase_date' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Create a pending purchase.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Create a received purchase.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
        ]);
    }

    /**
     * Create a partial purchase.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial',
        ]);
    }

    /**
     * Create a cancelled purchase.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create a purchase from a specific supplier.
     */
    public function fromSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
        ]);
    }

    /**
     * Create a purchase by a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a high-value purchase.
     */
    public function highValue(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->randomFloat(2, 1000, 10000);
            $taxAmount = $subtotal * 0.1; // 10% tax
            return [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ];
        });
    }

    /**
     * Create a purchase with expected delivery date.
     */
    public function withExpectedDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_delivery_date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Create a purchase from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_date' => now(),
        ]);
    }
}
