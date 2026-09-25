<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeadersMiddleware
 * Adds security headers to all responses.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS protection (legacy but still useful for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy (formerly Feature Policy)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Content Security Policy
        $isLocal = app()->environment('local');
        $viteOrigin = $isLocal ? 'http://127.0.0.1:5173' : '';

        $csp = [
            "default-src 'self'",
            "script-src 'self' https://www.googletagmanager.com https://accounts.google.com".($viteOrigin ? " {$viteOrigin}" : ''),
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com".($viteOrigin ? " {$viteOrigin}" : ''),
            "font-src 'self' data: https://fonts.gstatic.com".($viteOrigin ? " {$viteOrigin}" : ''),
            "img-src 'self' data: https:",
            "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://accounts.google.com".($viteOrigin ? " {$viteOrigin}" : ''),
            "frame-src 'self' https://accounts.google.com",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // HSTS - only enable in production with HTTPS
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
