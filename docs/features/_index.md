---
title: Features
weight: 50
---

A `Feature` is a capability or limit that varies by plan -- "Users", "API Calls", "GB of Storage", "SSL on/off". Features are standalone: create one, attach to many plans with different per-plan values.

- [Creating and attaching](/documentation/subscriptions/v2/features/creating-and-attaching) -- defining features and binding them to plans with values
- [Usage tracking](/documentation/subscriptions/v2/features/usage-tracking) -- recording consumption, querying remaining quota, automatic resets

## The data model

```
features            (definitions)
plan_features       (per-plan value pivot)
plan_subscription_usage  (recorded consumption per subscription)
```

A single `users` feature row can be attached to `basic` (`value=5`), `pro` (`value=50`), and `enterprise` (`value=999999`) -- one definition, three limits. This is the central design choice that distinguishes this package from rinvex/laravel-subscriptions's inline feature definitions.
