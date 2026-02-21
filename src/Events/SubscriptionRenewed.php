<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Events;

use Crumbls\Subscriptions\Models\PlanSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PlanSubscription $subscription,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }

    public function broadcastWhen(): bool
    {
        return config('subscriptions.broadcast_events', false);
    }
}
