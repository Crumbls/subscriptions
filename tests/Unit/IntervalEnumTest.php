<?php

use Carbon\Carbon;
use Crumbls\Subscriptions\Enums\Interval;

it('has the correct cases', function (): void {
    expect(Interval::cases())->toHaveCount(5);
    expect(Interval::Hour->value)->toBe('hour');
    expect(Interval::Day->value)->toBe('day');
    expect(Interval::Week->value)->toBe('week');
    expect(Interval::Month->value)->toBe('month');
    expect(Interval::Year->value)->toBe('year');
});

it('can be created from string', function (): void {
    expect(Interval::from('month'))->toBe(Interval::Month);
    expect(Interval::from('day'))->toBe(Interval::Day);
    expect(Interval::from('year'))->toBe(Interval::Year);
});

it('adds correct duration to date', function (): void {
    $date = Carbon::parse('2026-01-01 00:00:00');

    expect(Interval::Day->addToDate($date->copy(), 5)->toDateString())->toBe('2026-01-06');
    expect(Interval::Week->addToDate($date->copy(), 2)->toDateString())->toBe('2026-01-15');
    expect(Interval::Month->addToDate($date->copy(), 3)->toDateString())->toBe('2026-04-01');
    expect(Interval::Hour->addToDate($date->copy(), 24)->toDateTimeString())->toBe('2026-01-02 00:00:00');
    expect(Interval::Year->addToDate($date->copy(), 2)->toDateString())->toBe('2028-01-01');
});
