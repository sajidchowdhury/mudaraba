<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Dashboard', [
            'appName' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'name' => $user->employee?->name ?? $user->username,
                ] : null,
            ],
            'kpis' => [
                [
                    'label' => 'Total Mudaraba Investment',
                    'value' => 157475000,
                    'change' => '+4.2%',
                    'tone' => 'primary',
                    'hint' => '151 active investors',
                ],
                [
                    'label' => 'July 2026 Actual Profit',
                    'value' => 1635000,
                    'change' => '+8.6%',
                    'tone' => 'success',
                    'hint' => '17 sectors',
                ],
                [
                    'label' => 'M / Y Profit (July)',
                    'value' => 476220.07,
                    'change' => '29.13%',
                    'tone' => 'accent',
                    'hint' => 'of total actual profit',
                ],
                [
                    'label' => 'Active Investors',
                    'value' => 151,
                    'change' => '+3',
                    'tone' => 'info',
                    'hint' => 'across 3 tiers',
                ],
            ],
        ]);
    }
}
