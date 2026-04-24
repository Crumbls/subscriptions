<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Database\Factories;

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanSubscription>
 */
class PlanSubscriptionFactory extends Factory
{
    protected $model = PlanSubscription::class;

    public function modelName(): string
    {
        /** @var class-string<PlanSubscription> $model */
        $model = config('subscriptions.models.plan_subscription', PlanSubscription::class);

        return $model;
    }

    public function definition(): array
    {
        $now = Carbon::now();

        return [
            'plan_id' => PlanFactory::new(),
            'name' => 'main',
            'starts_at' => $now,
            'ends_at' => $now->copy()->addMonth(),
        ];
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state(['plan_id' => $plan->getKey()]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'starts_at' => Carbon::now()->subMonths(2),
            'ends_at' => Carbon::now()->subDay(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => ['canceled_at' => Carbon::now()]);
    }

    public function onTrial(int $remainingDays = 7): static
    {
        return $this->state(fn () => [
            'trial_ends_at' => Carbon::now()->addDays($remainingDays),
        ]);
    }
}
