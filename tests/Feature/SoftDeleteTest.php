<?php

use Crumbls\Subscriptions\Models\Feature;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Crumbls\Subscriptions\Models\PlanSubscriptionUsage;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->plan = Plan::factory()->create(['name' => 'Pro']);
    $feature = Feature::factory()->create(['name' => 'API Calls', 'slug' => 'api-calls']);
    $this->plan->features()->attach($feature, ['value' => '100']);

    $this->user = User::create(['name' => 'Test', 'email' => 'softdelete@example.com']);
});

it('cascades usage deletion when a subscription is soft-deleted', function (): void {
    $sub = $this->user->subscribe('main', $this->plan);
    $sub->recordFeatureUsage('api-calls', 5);

    expect(PlanSubscriptionUsage::where('subscription_id', $sub->id)->count())->toBe(1);

    $sub->delete();

    expect(PlanSubscription::withTrashed()->find($sub->id)->deleted_at)->not->toBeNull();
    expect(PlanSubscriptionUsage::where('subscription_id', $sub->id)->count())->toBe(0);
});

it('cascades usage deletion when a feature is soft-deleted', function (): void {
    $sub = $this->user->subscribe('main', $this->plan);
    $sub->recordFeatureUsage('api-calls', 5);

    $feature = Feature::where('slug', 'api-calls')->first();
    $feature->delete();

    expect(Feature::withTrashed()->find($feature->id)->deleted_at)->not->toBeNull();
    expect(PlanSubscriptionUsage::where('feature_id', $feature->id)->count())->toBe(0);
});

it('detaches features and soft-deletes subscriptions when a plan is soft-deleted', function (): void {
    $sub = $this->user->subscribe('main', $this->plan);

    $this->plan->delete();

    expect(Plan::withTrashed()->find($this->plan->id)->deleted_at)->not->toBeNull();
    expect($this->plan->features()->count())->toBe(0);
    expect(PlanSubscription::find($sub->id))->toBeNull();
    expect(PlanSubscription::withTrashed()->find($sub->id))->not->toBeNull();
});
