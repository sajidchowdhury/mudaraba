<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Display the premium login page.
     * (Authentication backend comes in Session 2.2 — this is UI-only.)
     */
    public function index()
    {
        return Inertia::render('Login', [
            'appName' => config('app.name'),
        ]);
    }
}
