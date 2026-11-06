<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply baseline security headers and a strict, nonce-based
     * Content-Security-Policy to every web response.
     *
     * The CSP nonce is generated per request and applied to the app shell's
     * inline scripts/styles and to every Vite-generated tag (via
     * Vite::useCspNonce()). In development (Vite hot server) the policy is
     * widened to allow the dev server origin and its HMR websocket.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate the nonce before the view renders so @vite / @fonts /
        // @viteReactRefresh and the shell's inline tags all pick it up.
        $nonce = app(Vite::class)->useCspNonce();
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->buildCspPolicy($nonce));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');

        if ($request->user() !== null
            || $request->is('*/cart*')
            || $request->is('*/checkout*')
            || $request->is('*/orders*')) {
            $response->headers->set(
                'Cache-Control',
                'private, no-store, max-age=0, must-revalidate',
            );
        }

        return $response;
    }

    /**
     * Build the strict Content-Security-Policy for the given nonce.
     */
    protected function buildCspPolicy(string $nonce): string
    {
        $script = ["'self'", "'nonce-{$nonce}'"];
        $style = ["'self'", "'nonce-{$nonce}'"];
        $connect = ["'self'"];
        $img = ["'self'", 'data:'];
        $font = ["'self'", 'data:'];

        $devServer = app(Vite::class)->devServerUrl();

        if ($devServer !== null) {
            $ws = preg_replace('#^http#', 'ws', rtrim($devServer, '/'));

            $script[] = $devServer;
            $style[] = $devServer;
            $style[] = "'unsafe-inline'"; // Vite dev injects styles via JS without a nonce
            $connect[] = $devServer;
            $connect[] = $ws;
            $img[] = $devServer;
            $font[] = $devServer;
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            'connect-src '.implode(' ', $connect),
            'img-src '.implode(' ', $img),
            'font-src '.implode(' ', $font),
        ]);
    }
}
