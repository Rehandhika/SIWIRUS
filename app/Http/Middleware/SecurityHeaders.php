<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Disable XSS protection as it can cause issues with modern browsers
        // $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy - temporarily disabled for debugging
        if (app()->environment('local', 'testing')) {
            // CSP disabled for development debugging
            // $response->headers->set('Content-Security-Policy',
            //     "default-src 'self'; " .
            //     "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://localhost:5174 ws://localhost:5173 ws://localhost:5174; " .
            //     "style-src 'self' 'unsafe-inline' http://localhost:5173 http://localhost:5174; " .
            //     "img-src 'self' data: https:; " .
            //     "font-src 'self' data: http://localhost:5173 http://localhost:5174; " .
            //     "connect-src 'self' ws://localhost:5173 ws://localhost:5174 http://localhost:5173 http://localhost:5174"
            // );
        } else {
            // Production CSP - allow Vite assets and external fonts
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; ".
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; ".
                "font-src 'self' data: https://fonts.bunny.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; ".
                "img-src 'self' data: https:; ".
                "connect-src 'self' https:; ".
                "object-src 'none'; ".
                "base-uri 'self'"
            );
        }

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
