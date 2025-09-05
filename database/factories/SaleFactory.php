<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 1000);
        $taxRate = fake()->randomFloat(2, 0.05, 0.15); // 5% to 15%
        $taxAmount = $subtotal * $taxRate;
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.2); // Up to 20% discount
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'sale_number' => null, // Will be auto-generated
            'customer_id' => fake()->optional(0.7)->randomElement([Customer::factory()]), // 70% chance of having a customer
            'user_id' => User::factory(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'points_earned' => fake()->optional(0.8)->numberBetween(1, 100), // 80% chance of earning points
            'payment_method' => fake()->randomElement(['cash', 'card', 'digital']),
            'payment_status' => fake()->randomElement(['completed', 'pending', 'refunded']),
            'notes' => fake()->optional(0.3)->sentence(),
            'sale_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a completed sale.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'completed',
        ]);
    }

    /**
     * Create a pending sale.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Create a refunded sale.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'refunded',
        ]);
    }

    /**
     * Create a sale for a specific customer.
     */
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Create a sale by a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a sale with loyalty points earned.
     */
    public function withLoyaltyPoints(int $points): static
    {
        return $this->state(fn (array $attributes) => [
            'points_earned' => $points,
        ]);
    }

    /**
     * Create a cash payment sale.
     */
    public function cashPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Create a card payment sale.
     */
    public function cardPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
        ]);
    }

    /**
     * Create a sale with discount.
     */
    public function withDiscount(float $discount): static
    {
        return $this->state(function (array $attributes) use ($discount) {
            $newTotal = $attributes['subtotal'] + $attributes['tax_amount'] - $discount;
            return [
                'discount_amount' => $discount,
                'total_amount' => $newTotal,
            ];
        });
    }

    /**
     * Create a high-value sale.
     */
    public function highValue(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->randomFloat(2, 500, 5000);
            $taxAmount = $subtotal * 0.1; // 10% tax
            return [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
                'points_earned' => floor($subtotal), // 1 point per dollar
            ];
        });
    }

    /**
     * Create a sale from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_date' => now(),
        ]);
    }
}
