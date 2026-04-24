<?php

use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\Team;
use Crumbls\Subscriptions\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->plan = Plan::factory()->create(['name' => 'Pro']);
});

it('allows different subscriber types to hold subscriptions with the same slug', function (): void {
    $user = User::create(['name' => 'User', 'email' => 'user@example.com']);
    $team = Team::create(['name' => 'Team One']);

    $userSub = $user->subscribe('main', $this->plan);
    $teamSub = $team->subscribe('main', $this->plan);

    expect($userSub->slug)->toBe('main');
    expect($teamSub->slug)->toBe('main');

    expect($user->planSubscription('main'))->not->toBeNull();
    expect($team->planSubscription('main'))->not->toBeNull();
    expect($user->planSubscription('main')->id)->not->toBe($teamSub->id);
});

it('still enforces slug uniqueness within a single subscriber', function (): void {
    $user = User::create(['name' => 'User', 'email' => 'user@example.com']);

    $first = $user->subscribe('main', $this->plan);
    $second = $user->subscribe('main', $this->plan);

    expect($first->slug)->toBe('main');
    expect($second->slug)->toBe('main-1');
});

it('scopes ofSubscriber correctly across polymorphic types', function (): void {
    $user = User::create(['name' => 'User', 'email' => 'user@example.com']);
    $team = Team::create(['name' => 'Team One']);

    $user->subscribe('main', $this->plan);
    $team->subscribe('main', $this->plan);

    expect(\Crumbls\Subscriptions\Models\PlanSubscription::ofSubscriber($user)->count())->toBe(1);
    expect(\Crumbls\Subscriptions\Models\PlanSubscription::ofSubscriber($team)->count())->toBe(1);
});
