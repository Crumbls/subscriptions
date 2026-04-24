<?php

use Crumbls\Subscriptions\Exceptions\UnknownFeatureException;
use Crumbls\Subscriptions\Models\Feature;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;

it('bySlug scope filters features by slug', function (): void {
    Feature::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
    Feature::factory()->create(['name' => 'Beta', 'slug' => 'beta']);

    expect(Feature::bySlug('alpha')->count())->toBe(1);
    expect(Feature::bySlug('alpha')->first()->slug)->toBe('alpha');
    expect(Feature::bySlug('nonexistent')->count())->toBe(0);
});

it('hasReset reports correctly', function (): void {
    $nonResettable = Feature::factory()->create(['resettable_period' => 0]);
    $resettable = Feature::factory()->resettableMonthly()->create();

    expect($nonResettable->hasReset())->toBeFalse();
    expect($resettable->hasReset())->toBeTrue();
});

it('throws UnknownFeatureException when recording usage for a feature not on the plan', function (): void {
    $plan = Plan::factory()->create(['name' => 'Basic']);
    $user = User::create(['name' => 'Test', 'email' => 'unknownfeat@example.com']);
    $sub = $user->subscribe('main', $plan);

    $sub->recordFeatureUsage('does-not-exist');
})->throws(UnknownFeatureException::class);

it('UnknownFeatureException exposes the feature slug and plan', function (): void {
    $plan = Plan::factory()->create(['name' => 'Basic']);

    $ex = new UnknownFeatureException('widgets', $plan);

    expect($ex->featureSlug)->toBe('widgets');
    expect($ex->plan->slug)->toBe($plan->slug);
    expect($ex->getMessage())->toContain('widgets');
    expect($ex->getMessage())->toContain($plan->slug);
});
