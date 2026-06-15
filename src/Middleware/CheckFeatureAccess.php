<?php

declare(strict_types=1);

namespace Salehye\Subscription\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckFeatureAccess
{
    /**
     * Handle an incoming request.
     *
     * Usage: 'subscription.feature:feature_slug' or 'subscription.feature:feature_slug,redirect'
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureSlug, string $failAction = 'abort'): Response
    {
        $user = Auth::user();

        if ($user === null) {
            if ($failAction === 'redirect') {
                return redirect()->route('login');
            }

            throw new HttpException(401, 'Unauthenticated.');
        }

        /** @var \Salehye\Subscription\Contracts\HasSubscriptions $user */
        if (!method_exists($user, 'canAccessFeature')) {
            throw new \RuntimeException('The authenticated entity must use the HasSubscriptions trait.');
        }

        if (!$user->canAccessFeature($featureSlug)) {
            if ($failAction === 'redirect') {
                return redirect()->route('subscription.plans')
                    ->with('error', "You need an upgraded plan to access: {$featureSlug}");
            }

            throw new HttpException(403, "You do not have access to the '{$featureSlug}' feature.");
        }

        return $next($request);
    }
}
