---
title: Introduction
weight: 10
---

`crumbls/subscriptions` is a plans-and-subscriptions library for Laravel 11, 12, and 13 on PHP 8.3+. It manages the *logic* of SaaS subscriptions -- plans, features, trial / grace windows, usage tracking, lifecycle events -- without coupling to any payment provider. You bring Stripe, Paddle, Lemon Squeezy, or whatever else; this package handles the rest.

## What it does

- **Plans** -- pricing, currency, billing cycle, trial period, grace window, signup fee, subscriber cap
- **Features** -- standalone entities attached to plans with per-plan values ("Basic gets 5 users, Pro gets 50")
- **Subscriptions** -- polymorphic, so any model (User, Tenant, Team, Organization) can subscribe
- **Usage tracking** -- record feature consumption, query remaining quota, automatic resets on a configurable interval
- **Lifecycle** -- created, canceled, renewed, plan-changed events for hooking payment workflows
- **Middleware** -- gate routes by feature availability or active subscription
- **Pruning** -- artisan command to soft-delete expired subscriptions

## What it does not do

- **Payments.** No Stripe, no Paddle, no PayPal integration. Payment workflows belong in your application -- subscribe to the lifecycle events or extend the models.
- **Invoicing or receipts.** Out of scope.
- **Coupons or promos.** Use your payment provider's primitives.
- **Multi-currency conversion.** A plan's `currency` is a label; conversion is your job.

## Coming from rinvex/laravel-subscriptions

This package started as a modern reboot of the abandoned `rinvex/laravel-subscriptions`. The mental model is similar; namespaces, config keys, and a few APIs differ. See [Migrating from rinvex](/documentation/subscriptions/v2/advanced/migrating-from-rinvex) for the full delta.

## What's next

- [Installation](/documentation/subscriptions/v2/installation)
- [Getting started](/documentation/subscriptions/v2/getting-started)
- [Filament admin panel](https://github.com/Crumbls/subscriptions-filament) -- separate package, full CRUD UI
