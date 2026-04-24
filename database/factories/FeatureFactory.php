<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Database\Factories;

use Crumbls\Subscriptions\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function modelName(): string
    {
        /** @var class-string<Feature> $model */
        $model = config('subscriptions.models.feature', Feature::class);

        return $model;
    }

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'resettable_period' => 0,
            'resettable_interval' => 'month',
        ];
    }

    public function resettableMonthly(): static
    {
        return $this->state([
            'resettable_period' => 1,
            'resettable_interval' => 'month',
        ]);
    }

    public function resettableDaily(): static
    {
        return $this->state([
            'resettable_period' => 1,
            'resettable_interval' => 'day',
        ]);
    }
}
