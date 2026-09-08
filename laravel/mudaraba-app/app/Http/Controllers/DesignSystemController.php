<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DesignSystemController extends Controller
{
    public function index()
    {
        return Inertia::render('DesignSystem', [
            'appName' => config('app.name'),
        ]);
    }
}
