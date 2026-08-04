<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_super_admin) {
            abort(Response::HTTP_FORBIDDEN, __('Accès administrateur requis.'));
        }

        if (!$request->user()->last_seen_at || $request->user()->last_seen_at->diffInMinutes(now()) >= 5) {
            $request->user()->withoutEvents(fn () => $request->user()->updateQuietly(['last_seen_at' => now()]));
        }

        return $next($request);
    }
}
