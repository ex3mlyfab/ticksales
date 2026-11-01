<?php

use function Pest\Laravel\get;

test('web responses carry baseline security headers', function () {
    get('/login')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('web responses carry a strict nonce-based content security policy', function () {
    $response = get('/login')->assertOk();

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toBeString()
        ->and($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("base-uri 'self'")
        ->and($csp)->toContain("form-action 'self'")
        // Strict policy: no unsafe-inline in the script or style directives.
        ->and($csp)->not->toContain("'unsafe-inline'");
});

test('the csp nonce is applied to the shell inline script and style', function () {
    $response = get('/login')->assertOk();

    $csp = $response->headers->get('Content-Security-Policy');

    preg_match("/'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->toBeString()->not->toBeEmpty();

    $html = $response->getContent();

    // Both the dark-mode script and the inline style carry the matching nonce.
    expect($html)->toContain('nonce="'.$nonce.'"');
    expect(substr_count($html, 'nonce="'.$nonce.'"'))->toBeGreaterThanOrEqual(2);
    // The Inertia page-data script is nonced too.
    expect($html)->toContain('<script data-page="app" type="application/json" nonce="'.$nonce.'">');
    // Every Vite-generated script/link tag is nonced as well.
    preg_match_all('/<(?:script|link)[^>]*>/', $html, $tags);

    foreach ($tags[0] as $tag) {
        if (str_contains($tag, 'build/') || str_contains($tag, 'vite') || str_contains($tag, 'manifest')) {
            expect($tag)->toContain('nonce="'.$nonce.'"');
        }
    }
});

test('each request receives a fresh csp nonce', function () {
    $first = get('/login')->assertOk()->headers->get('Content-Security-Policy');
    $second = get('/login')->assertOk()->headers->get('Content-Security-Policy');

    expect($first)->not->toBe($second);
});
