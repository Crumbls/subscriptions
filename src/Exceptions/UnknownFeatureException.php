<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Exceptions;

use Crumbls\Subscriptions\Models\Plan;
use RuntimeException;

class UnknownFeatureException extends RuntimeException
{
    public function __construct(
        public readonly string $featureSlug,
        public readonly Plan $plan,
    ) {
        parent::__construct(
            "Feature [{$featureSlug}] is not attached to plan [{$plan->slug}]."
        );
    }
}
