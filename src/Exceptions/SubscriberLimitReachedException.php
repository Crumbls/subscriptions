<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Exceptions;

use Crumbls\Subscriptions\Models\Plan;
use RuntimeException;

class SubscriberLimitReachedException extends RuntimeException
{
    public function __construct(
        public readonly Plan $plan,
        public readonly int $limit,
        public readonly int $currentCount,
    ) {
        parent::__construct(
            "Plan [{$plan->slug}] has reached its active subscriber limit of {$limit} (current: {$currentCount})."
        );
    }
}
