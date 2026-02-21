<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscribedTo
{
    /**
     * Verify the authenticated user has an active subscription.
     *
     * Usage:
     *   Route::middleware('subscribed')           → any active subscription
     *   Route::middleware('subscribed:pro')        → subscription with slug "pro"
     *   Route::middleware('subscribed:pro,enterprise') → either slug
     */
    public function handle(Request $request, Closure $next, string ...$slugs): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'planSubscriptions')) {
            return $this->deny($request);
        }

        if (empty($slugs)) {
            // Any active subscription
            if (! $user->hasActiveSubscription()) {
                return $this->deny($request);
            }
        } else {
            // Must have an active subscription matching one of the given slugs
            $hasMatch = $user->planSubscriptions()
                ->whereIn('slug', $slugs)
                ->get()
                ->contains(fn ($sub) => $sub->active());

            if (! $hasMatch) {
                return $this->deny($request);
            }
        }

        return $next($request);
    }

    protected function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Active subscription required.'], 403);
        }

        abort(403, 'Active subscription required.');
    }
}
