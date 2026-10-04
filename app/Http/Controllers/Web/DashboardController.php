<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the tenant dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }
}
