<?php

use Crumbls\Subscriptions\Exceptions\SubscriberLimitReachedException;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;

it('allows subscription when no limit is set', function () {
    $plan = Plan::create([
        'name' => 'Unlimited',
        'price' => 10,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);

    expect($plan->hasSubscriberLimit())->toBeFalse()
        ->and($plan->canAcceptNewSubscriber())->toBeTrue();

    foreach (range(1, 5) as $i) {
        $user = User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com"]);
        $user->newPlanSubscription('main', $plan);
    }

    expect($plan->activeSubscriberCount())->toBe(5)
        ->and($plan->canAcceptNewSubscriber())->toBeTrue();
});

it('enforces subscriber limit', function () {
    $plan = Plan::create([
        'name' => 'Limited',
        'price' => 10,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'active_subscribers_limit' => 2,
    ]);

    expect($plan->hasSubscriberLimit())->toBeTrue()
        ->and($plan->canAcceptNewSubscriber())->toBeTrue();

    $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
    $user1->newPlanSubscription('main', $plan);

    $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
    $user2->newPlanSubscription('main', $plan);

    expect($plan->canAcceptNewSubscriber())->toBeFalse()
        ->and($plan->activeSubscriberCount())->toBe(2);

    $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);

    expect(fn () => $user3->newPlanSubscription('main', $plan))
        ->toThrow(SubscriberLimitReachedException::class);
});

it('frees a slot when a subscription is canceled immediately', function () {
    $plan = Plan::create([
        'name' => 'Limited',
        'price' => 10,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'active_subscribers_limit' => 1,
    ]);

    $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
    $sub = $user1->newPlanSubscription('main', $plan);

    expect($plan->canAcceptNewSubscriber())->toBeFalse();

    $sub->cancel(immediately: true);

    expect($plan->canAcceptNewSubscriber())->toBeTrue();

    $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
    $user2->newPlanSubscription('main', $plan);

    expect($plan->activeSubscriberCount())->toBe(1);
});

it('includes the plan and counts in the exception', function () {
    $plan = Plan::create([
        'name' => 'Tiny',
        'price' => 10,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'active_subscribers_limit' => 1,
    ]);

    $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
    $user1->newPlanSubscription('main', $plan);

    $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

    try {
        $user2->newPlanSubscription('main', $plan);
        $this->fail('Expected exception');
    } catch (SubscriberLimitReachedException $e) {
        expect($e->plan->id)->toBe($plan->id)
            ->and($e->limit)->toBe(1)
            ->and($e->currentCount)->toBe(1)
            ->and($e->getMessage())->toContain('tiny');
    }
});
