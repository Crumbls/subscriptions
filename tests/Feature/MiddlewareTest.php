<?php

use Crumbls\Subscriptions\Http\Middleware\CanUseFeature;
use Crumbls\Subscriptions\Http\Middleware\SubscribedTo;
use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Tests\Fixtures\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->plan = Plan::create([
        'name' => 'Pro',
        'price' => 10,
        'signup_fee' => 0,
        'currency' => 'USD',
        'invoice_period' => 1,
        'invoice_interval' => 'month',
    ]);
});

function makeRequest(User $user): Request
{
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    return $request;
}

function passThrough(): Closure
{
    return fn () => new Response('ok', 200);
}

// ── SubscribedTo ────────────────────────────────────────

it('allows access with any active subscription', function () {
    $this->user->newPlanSubscription('main', $this->plan);

    $middleware = new SubscribedTo;
    $response = $middleware->handle(makeRequest($this->user), passThrough());

    expect($response->getStatusCode())->toBe(200);
});

it('denies access without subscription', function () {
    $middleware = new SubscribedTo;

    expect(fn () => $middleware->handle(makeRequest($this->user), passThrough()))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('allows access with matching subscription slug', function () {
    $this->user->newPlanSubscription('pro', $this->plan);

    $middleware = new SubscribedTo;
    $response = $middleware->handle(makeRequest($this->user), passThrough(), 'pro');

    expect($response->getStatusCode())->toBe(200);
});

it('denies access with non-matching subscription slug', function () {
    $this->user->newPlanSubscription('basic', $this->plan);

    $middleware = new SubscribedTo;

    expect(fn () => $middleware->handle(makeRequest($this->user), passThrough(), 'pro'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('returns json 403 for api requests without subscription', function () {
    $request = makeRequest($this->user);
    $request->headers->set('Accept', 'application/json');

    $middleware = new SubscribedTo;
    $response = $middleware->handle($request, passThrough());

    expect($response->getStatusCode())->toBe(403);
});

// ── CanUseFeature ───────────────────────────────────────

it('allows access when feature is available', function () {
    $this->plan->features()->create([
        'name' => 'API Calls',
        'slug' => 'api-calls',
        'resettable_period' => 1,
        'resettable_interval' => 'month',
    ], ['value' => '100']);

    $this->user->newPlanSubscription('main', $this->plan);

    $middleware = new CanUseFeature;
    $response = $middleware->handle(makeRequest($this->user), passThrough(), 'api-calls');

    expect($response->getStatusCode())->toBe(200);
});

it('denies access when feature is exhausted', function () {
    $this->plan->features()->create([
        'name' => 'API Calls',
        'slug' => 'api-calls',
        'resettable_period' => 1,
        'resettable_interval' => 'month',
    ], ['value' => '1']);

    $sub = $this->user->newPlanSubscription('main', $this->plan);
    $sub->recordFeatureUsage('api-calls', 1);

    $middleware = new CanUseFeature;

    expect(fn () => $middleware->handle(makeRequest($this->user), passThrough(), 'api-calls'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('returns json 403 for api requests when feature unavailable', function () {
    $request = makeRequest($this->user);
    $request->headers->set('Accept', 'application/json');

    $middleware = new CanUseFeature;
    $response = $middleware->handle($request, passThrough(), 'api-calls');

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('api-calls');
});
