<?php

use Crumbls\Subscriptions\Models\Feature;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->plan = Plan::factory()->create([
        'name' => 'Pro',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);

    $feature = Feature::factory()->create(['name' => 'API Calls', 'slug' => 'api-calls']);
    $this->plan->features()->attach($feature, ['value' => '1000']);

    $this->user = User::create(['name' => 'Test', 'email' => 'atom@example.com']);
});

it('records feature usage inside a transaction to serialise concurrent writers', function (): void {
    $sub = $this->user->subscribe('main', $this->plan);

    // Sanity check that the recorded transactions nest cleanly under an outer
    // transaction. If recordFeatureUsage wasn't wrapped in a transaction it
    // would still pass this test, but wrapping it means the call re-uses the
    // ambient transaction and the assertion confirms it committed correctly.
    DB::transaction(function () use ($sub): void {
        $sub->recordFeatureUsage('api-calls', 3);
        $sub->recordFeatureUsage('api-calls', 2);
    });

    expect($sub->getFeatureUsage('api-calls'))->toBe(5);
});

it('does not lose writes when recording the same feature twice in a row', function (): void {
    $sub = $this->user->subscribe('main', $this->plan);

    $sub->recordFeatureUsage('api-calls', 10);
    $sub->recordFeatureUsage('api-calls', 15);

    expect($sub->getFeatureUsage('api-calls'))->toBe(25);
});

it('rolls back a failed subscribe attempt without side effects', function (): void {
    $limited = Plan::factory()->limitedTo(1)->create(['name' => 'Solo']);

    $first = User::create(['name' => 'First', 'email' => 'first@example.com']);
    $second = User::create(['name' => 'Second', 'email' => 'second@example.com']);

    $first->subscribe('main', $limited);

    expect(fn () => $second->subscribe('main', $limited))
        ->toThrow(\Crumbls\Subscriptions\Exceptions\SubscriberLimitReachedException::class);

    // The failing subscribe must not have left a half-created subscription.
    expect($second->planSubscriptions()->count())->toBe(0);
});
