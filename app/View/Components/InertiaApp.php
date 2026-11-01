<?php

namespace App\View\Components;

use Illuminate\Foundation\Vite;
use Inertia\View\Components\App;

/**
 * Inertia app shell component that applies the Content-Security-Policy
 * nonce to the inline page-data script so a strict CSP can allow it.
 *
 * The page data is a non-executable `application/json` block (exempt from
 * CSP inline restrictions per CSP3), but carrying the nonce keeps the shell
 * robust against stricter policies and tools that scan all script elements.
 */
class InertiaApp extends App
{
    public string $nonce = '';

    public function __construct(string $id = 'app')
    {
        parent::__construct($id);

        $this->nonce = app(Vite::class)->cspNonce() ?? '';
    }

    public function render(): string
    {
        return <<<'blade'
@if($response)
{!! $response->body !!}
@else
<script data-page="{{ $id }}" type="application/json"{!! $nonce !== '' ? ' nonce="'.$nonce.'"' : '' !!}>{!! $pageJson !!}</script><div id="{{ $id }}"></div>
@endif
blade;
    }
}
