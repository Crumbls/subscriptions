<?php

use Crumbls\Subscriptions\Console\PruneExpiredSubscriptionsCommand;
use Crumbls\Subscriptions\Http\Middleware\CanUseFeature;
use Crumbls\Subscriptions\Http\Middleware\SubscribedTo;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Router;

it('loads the subscriptions config', function (): void {
    expect(config('subscriptions.autoload_migrations'))->toBeTrue();
    expect(config('subscriptions.tables.plans'))->toBe('plans');
    expect(config('subscriptions.models.plan'))->toBe(\Crumbls\Subscriptions\Models\Plan::class);
});

it('registers the subscribed middleware alias', function (): void {
    $middleware = app(Router::class)->getMiddleware();

    expect($middleware)->toHaveKey('subscribed');
    expect($middleware['subscribed'])->toBe(SubscribedTo::class);
});

it('registers the can-use-feature middleware alias', function (): void {
    $middleware = app(Router::class)->getMiddleware();

    expect($middleware)->toHaveKey('can-use-feature');
    expect($middleware['can-use-feature'])->toBe(CanUseFeature::class);
});

it('registers the prune command', function (): void {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKey('subscriptions:prune');
    expect($commands['subscriptions:prune'])->toBeInstanceOf(PruneExpiredSubscriptionsCommand::class);
});
