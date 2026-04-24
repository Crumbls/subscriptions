<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Events;

use Crumbls\Subscriptions\Models\Plan;
use Crumbls\Subscriptions\Models\PlanSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPlanChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PlanSubscription $subscription,
        public readonly Plan $oldPlan,
        public readonly Plan $newPlan,
    ) {}
}
