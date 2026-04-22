<?php

use Crumbls\Subscriptions\Models\Plan;

it('can create a plan', function (): void {
    $plan = Plan::create([
        'name' => 'Pro',
        'description' => 'Pro plan',
        'price' => 9.99,
        'signup_fee' => 1.99,
        'invoice_period' => 1,
        'invoice_interval' => 'month',
        'trial_period' => 15,
        'trial_interval' => 'day',
        'currency' => 'USD',
    ]);

    $plan->refresh();

    expect($plan)->toBeInstanceOf(Plan::class)
        ->and($plan->slug)->toBe('pro')
        ->and($plan->is_active)->toBeTrue()
        ->and((float) $plan->price)->toBe(9.99)
        ->and($plan->invoice_interval->value)->toBe('month');
});

it('generates a unique slug from name', function (): void {
    Plan::create(['name' => 'Basic', 'price' => 0, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);
    $plan2 = Plan::create(['name' => 'Basic', 'price' => 5, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);

    expect($plan2->slug)->not->toBe('basic');
});

it('knows if it is free', function (): void {
    $free = Plan::create(['name' => 'Free', 'price' => 0, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);
    $paid = Plan::create(['name' => 'Paid', 'price' => 10, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);

    expect($free->isFree())->toBeTrue()
        ->and($paid->isFree())->toBeFalse();
});

it('knows if it has a trial', function (): void {
    $withTrial = Plan::create(['name' => 'Trial', 'price' => 10, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month', 'trial_period' => 14, 'trial_interval' => 'day']);
    $noTrial = Plan::create(['name' => 'No Trial', 'price' => 10, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);

    expect($withTrial->hasTrial())->toBeTrue()
        ->and($noTrial->hasTrial())->toBeFalse();
});

it('can be activated and deactivated', function (): void {
    $plan = Plan::create(['name' => 'Toggle', 'price' => 10, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);

    $plan->deactivate();
    expect($plan->fresh()->is_active)->toBeFalse();

    $plan->activate();
    expect($plan->fresh()->is_active)->toBeTrue();
});

it('can have features', function (): void {
    $plan = Plan::create(['name' => 'Featured', 'price' => 10, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);

    $plan->features()->create([
        'name' => 'API Calls',
        'slug' => 'api-calls',
        'resettable_period' => 1,
        'resettable_interval' => 'month',
    ], ['value' => '1000']);

    expect($plan->features)->toHaveCount(1)
        ->and($plan->getFeatureBySlug('api-calls')->pivot->value)->toBe('1000');
});

it('cascades deletes to features and subscriptions', function (): void {
    $plan = Plan::create(['name' => 'Deletable', 'price' => 10, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);

    $plan->features()->create(['name' => 'Feat', 'slug' => 'feat'], ['value' => '1']);

    $plan->delete();

    expect(Plan::withTrashed()->find($plan->id))->not->toBeNull()
        ->and($plan->features()->count())->toBe(0);
});
