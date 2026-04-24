---
title: Subscriptions
weight: 60
---

A `PlanSubscription` is one subscriber's subscription to one plan. It carries the trial / grace / cancellation state and references the consumption rows.

- [Status and lifecycle](/documentation/subscriptions/v2/subscriptions/status-and-lifecycle) -- creating, status checks, change-plan, renew, cancel, reactivate
- [Scopes](/documentation/subscriptions/v2/subscriptions/scopes) -- query scopes for finding active / ending / canceled / trial subscriptions

## The shape

```
plan_subscriptions
├── id
├── subscriber_id, subscriber_type    (polymorphic owner)
├── plan_id                           (which plan)
├── slug                              (unique per subscriber)
├── name, description                 (translatable JSON; usually inherited from plan)
├── trial_ends_at
├── starts_at, ends_at
├── cancels_at, canceled_at
└── timestamps + soft deletes
```

A subscription's `slug` is unique per subscriber, not globally. One subscriber can have multiple subscriptions (`main`, `addon-storage`, `beta`).

The `name` and `description` columns let you customize the visible name on a per-subscription basis (e.g. for grandfathered legacy plan names) without touching the plan itself.
