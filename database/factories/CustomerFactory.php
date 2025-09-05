<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => fake()->unique()->regexify('\+1[0-9]{10}'), // US phone format
            'name' => fake()->optional(0.9)->name(), // 90% chance of having a name
            'email' => fake()->optional(0.7)->unique()->safeEmail(),
            'address' => fake()->optional(0.8)->address(),
            'birthdate' => fake()->optional(0.6)->dateTimeBetween('-80 years', '-18 years'),
            'loyalty_points' => fake()->numberBetween(0, 10000),
            'is_active' => fake()->boolean(95), // 95% chance of being active
        ];
    }

    /**
     * Indicate that the customer should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a customer with loyalty points.
     */
    public function withLoyaltyPoints(int $points): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => $points,
        ]);
    }

    /**
     * Create a customer without loyalty points.
     */
    public function withoutLoyaltyPoints(): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => 0,
        ]);
    }

    /**
     * Create a customer with high loyalty points.
     */
    public function vipCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => fake()->numberBetween(5000, 50000),
        ]);
    }

    /**
     * Create a customer with a specific phone number.
     */
    public function withPhone(string $phone): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $phone,
        ]);
    }

    /**
     * Create a customer with complete profile.
     */
    public function completeProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'birthdate' => fake()->dateTimeBetween('-80 years', '-18 years'),
        ]);
    }

    /**
     * Create a customer with minimal data (phone only).
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'email' => null,
            'address' => null,
            'birthdate' => null,
        ]);
    }
}
