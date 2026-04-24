<?php

declare(strict_types=1);

return [

    // Automatically load migrations from the package
    'autoload_migrations' => true,

    // Database table names
    'tables' => [
        'plans' => 'plans',
        'features' => 'features',
        'plan_features' => 'plan_features',
        'plan_subscriptions' => 'plan_subscriptions',
        'plan_subscription_usage' => 'plan_subscription_usage',
    ],

    // Model classes (override to use your own)
    'models' => [
        'plan' => \Crumbls\Subscriptions\Models\Plan::class,
        'feature' => \Crumbls\Subscriptions\Models\Feature::class,
        'plan_feature' => \Crumbls\Subscriptions\Models\PlanFeature::class,
        'plan_subscription' => \Crumbls\Subscriptions\Models\PlanSubscription::class,
        'plan_subscription_usage' => \Crumbls\Subscriptions\Models\PlanSubscriptionUsage::class,
    ],

];
