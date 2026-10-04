<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;

abstract class Controller
{
    protected function currentTenant(): mixed
    {
        return app(TenantContext::class)->getTenant();
    }
}
