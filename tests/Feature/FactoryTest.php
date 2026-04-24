<?php

use Crumbls\Subscriptions\Models\Feature;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Crumbls\Subscriptions\Models\PlanSubscriptionUsage;
use Crumbls\Subscriptions\Tests\Fixtures\User;

it('creates plans with sensible defaults', function (): void {
    $plan = Plan::factory()->create();

    expect($plan->exists)->toBeTrue();
    expect($plan->slug)->not->toBeEmpty();
    expect($plan->invoice_interval->value)->toBe('month');
});

it('supports state methods on PlanFactory', function (): void {
    expect(Plan::factory()->free()->make()->price)->toBe('0.00');
    expect(Plan::factory()->withTrial(30)->make()->trial_period)->toBe(30);
    expect(Plan::factory()->withGrace(14)->make()->grace_period)->toBe(14);
    expect(Plan::factory()->limitedTo(5)->make()->active_subscribers_limit)->toBe(5);
    expect(Plan::factory()->inactive()->make()->is_active)->toBeFalse();
});

it('creates features and subscriptions via factory', function (): void {
    $feature = Feature::factory()->resettableMonthly()->create();
    expect($feature->hasReset())->toBeTrue();
    expect($feature->resettable_period)->toBe(1);

    $user = User::create(['name' => 'Factory User', 'email' => 'factory@example.com']);
    $subscription = PlanSubscription::factory()
        ->for($user, 'subscriber')
        ->create();

    expect($subscription->exists)->toBeTrue();
    expect($subscription->plan)->not->toBeNull();
    expect($subscription->subscriber->is($user))->toBeTrue();

    $usage = PlanSubscriptionUsage::factory()
        ->for($subscription, 'subscription')
        ->create();
    expect($usage->exists)->toBeTrue();
});
