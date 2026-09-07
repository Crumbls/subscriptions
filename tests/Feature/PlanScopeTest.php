<?php

use Crumbls\Subscriptions\Models\Plan;

beforeEach(function () {
    Plan::create(['name' => 'Free', 'price' => 0, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);
    Plan::create(['name' => 'Pro', 'price' => 1000, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month']);
    Plan::create(['name' => 'Disabled', 'price' => 500, 'signup_fee' => 0, 'currency' => 'USD', 'invoice_period' => 1, 'invoice_interval' => 'month', 'is_active' => false]);
});

it('scopes active plans', function () {
    expect(Plan::active()->count())->toBe(2);
});

it('scopes inactive plans', function () {
    expect(Plan::inactive()->count())->toBe(1);
});

it('scopes free plans', function () {
    expect(Plan::free()->count())->toBe(1);
});

it('scopes paid plans', function () {
    expect(Plan::paid()->count())->toBe(2);
});

it('chains scopes', function () {
    expect(Plan::active()->paid()->count())->toBe(1)
        ->and(Plan::active()->paid()->first()->name)->toBe('Pro');
});
