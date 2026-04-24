<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->user = User::create(['name' => 'Test', 'email' => 'scope@example.com']);

    $this->plan = Plan::factory()->create([
        'name' => 'Pro',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'trial_period' => 14,
        'trial_interval' => 'day',
    ]);
});

it('findEndingTrial returns subscriptions whose trial ends within the range', function (): void {
    Carbon::setTestNow('2026-01-01 00:00:00');
    $this->user->subscribe('main', $this->plan);

    Carbon::setTestNow('2026-01-13 00:00:00');

    expect(PlanSubscription::findEndingTrial(3)->count())->toBe(1);
    expect(PlanSubscription::findEndingTrial(1)->count())->toBe(0);

    Carbon::setTestNow();
});

it('findEndedTrial returns subscriptions whose trial has already ended', function (): void {
    Carbon::setTestNow('2026-01-01 00:00:00');
    $this->user->subscribe('main', $this->plan);

    Carbon::setTestNow('2026-02-01 00:00:00');

    expect(PlanSubscription::findEndedTrial()->count())->toBe(1);

    Carbon::setTestNow();
});

it('findEndingPeriod returns subscriptions ending within the range', function (): void {
    $shortPlan = Plan::factory()->create([
        'name' => 'Short',
        'invoice_period' => 7,
        'invoice_interval' => 'day',
    ]);

    Carbon::setTestNow('2026-01-01 00:00:00');
    $this->user->subscribe('short', $shortPlan);

    Carbon::setTestNow('2026-01-06 00:00:00');

    expect(PlanSubscription::findEndingPeriod(3)->count())->toBe(1);
    expect(PlanSubscription::findEndingPeriod(0)->count())->toBe(0);

    Carbon::setTestNow();
});

it('byPlanId scopes subscriptions to a given plan', function (): void {
    $otherPlan = Plan::factory()->create(['name' => 'Other']);
    $this->user->subscribe('main', $this->plan);
    $this->user->subscribe('side', $otherPlan);

    expect(PlanSubscription::byPlanId($this->plan->getKey())->count())->toBe(1);
    expect(PlanSubscription::byPlanId($otherPlan->getKey())->count())->toBe(1);
});
