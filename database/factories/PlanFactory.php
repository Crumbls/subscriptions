<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Database\Factories;

use Crumbls\Subscriptions\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function modelName(): string
    {
        /** @var class-string<Plan> $model */
        $model = config('subscriptions.models.plan', Plan::class);

        return $model;
    }

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 0, 499),
            'signup_fee' => 0,
            'currency' => 'USD',
            'trial_period' => 0,
            'trial_interval' => 'day',
            'invoice_period' => 1,
            'invoice_interval' => 'month',
            'grace_period' => 0,
            'grace_interval' => 'day',
        ];
    }

    public function free(): static
    {
        return $this->state(['price' => 0]);
    }

    public function paid(): static
    {
        return $this->state(['price' => $this->faker->randomFloat(2, 1, 499)]);
    }

    public function withTrial(int $days = 14): static
    {
        return $this->state([
            'trial_period' => $days,
            'trial_interval' => 'day',
        ]);
    }

    public function withGrace(int $days = 7): static
    {
        return $this->state([
            'grace_period' => $days,
            'grace_interval' => 'day',
        ]);
    }

    public function limitedTo(int $subscribers): static
    {
        return $this->state(['active_subscribers_limit' => $subscribers]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
