<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanUseFeature
{
    /**
     * Verify the authenticated user can use a given feature.
     *
     * Usage:
     *   Route::middleware('can-use-feature:api-calls')
     *   Route::middleware('can-use-feature:api-calls,pro')  → feature on subscription slug "pro"
     */
    public function handle(Request $request, Closure $next, string $featureSlug, ?string $subscriptionSlug = null): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'planSubscriptions')) {
            return $this->deny($request, $featureSlug);
        }

        if ($subscriptionSlug) {
            $subscription = $user->planSubscription($subscriptionSlug);
        } else {
            $subscription = $user->currentSubscription();
        }

        if (! $subscription || ! $subscription->active() || ! $subscription->canUseFeature($featureSlug)) {
            return $this->deny($request, $featureSlug);
        }

        return $next($request);
    }

    protected function deny(Request $request, string $featureSlug): Response
    {
        $message = "Feature [{$featureSlug}] is not available.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}
