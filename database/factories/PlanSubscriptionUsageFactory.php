<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Database\Factories;

use Crumbls\Subscriptions\Models\PlanSubscriptionUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanSubscriptionUsage>
 */
class PlanSubscriptionUsageFactory extends Factory
{
    protected $model = PlanSubscriptionUsage::class;

    public function modelName(): string
    {
        /** @var class-string<PlanSubscriptionUsage> $model */
        $model = config('subscriptions.models.plan_subscription_usage', PlanSubscriptionUsage::class);

        return $model;
    }

    public function definition(): array
    {
        return [
            'subscription_id' => PlanSubscriptionFactory::new(),
            'feature_id' => FeatureFactory::new(),
            'used' => $this->faker->numberBetween(0, 100),
            'valid_until' => null,
        ];
    }
}
