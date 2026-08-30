<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\MonthlyProfitSummary;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // KPI 1: Total Mudaraba Investment (sum of all investor due ledgers)
        $totalInvestment = (float) InvestorDueLedger::sum('due');

        // KPI 2: Current month actual profit
        $currentMonth = date('Y-m-01');
        $currentSummary = MonthlyProfitSummary::find($currentMonth);
        $currentMonthProfit = $currentSummary ? (float) $currentSummary->total_actual_profit : 0;

        // KPI 3: M/Y profit for current month
        $myProfit = $currentSummary ? (float) $currentSummary->my_profit : 0;
        $myRatio = $currentSummary ? (float) $currentSummary->my_profit_ratio : 0;

        // KPI 4: Active investors
        $activeInvestors = Investor::where('status', 'active')->count();
        $totalInvestors = Investor::count();

        // Monthly trend (last 6 months)
        $trendMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-{$i} months"));
            $summary = MonthlyProfitSummary::find($date);
            $trendMonths[] = [
                'month' => $date,
                'label' => date('M Y', strtotime($date)),
                'estimated' => $summary ? (float) $summary->total_estimated_profit : 0,
                'actual' => $summary ? (float) $summary->total_actual_profit : 0,
                'my_profit' => $summary ? (float) $summary->my_profit : 0,
            ];
        }

        // Sector allocation (top sectors by due balance)
        $sectorAllocation = Sector::with('dueLedger')
            ->where('status', 'active')
            ->get()
            ->map(fn (Sector $s) => [
                'name' => $s->name,
                'value' => (float) ($s->dueLedger?->due ?? 0),
            ])
            ->sortByDesc('value')
            ->take(8)
            ->values()
            ->toArray();

        // Investor tier distribution
        $tier100 = Investor::where('deed_ratio', '100')->where('status', 'active')->count();
        $tier80 = Investor::where('deed_ratio', '80')->where('status', 'active')->count();
        $tier60 = Investor::where('deed_ratio', '60')->where('status', 'active')->count();

        // Recent activity (last 10 audit logs)
        $recentActivity = AuditLog::with('user:id,username')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'user' => $log->user?->username ?? 'system',
                'created_at' => $log->created_at?->diffForHumans(),
            ]);

        // If no audit logs, build from recent transactions
        if ($recentActivity->isEmpty()) {
            $recentTx = InvestmentTransaction::with(['investor:id,name', 'creator:id,username'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
            $recentProfit = MonthlyProfitSummary::orderByDesc('profit_month')
                ->limit(3)
                ->get();

            $recentActivity = collect();
            foreach ($recentTx as $tx) {
                $recentActivity->push([
                    'action' => $tx->type->value === 'add' ? 'Investment Added' : 'Investment Withdrawn',
                    'entity_type' => 'InvestmentTransaction',
                    'user' => $tx->creator?->username ?? '—',
                    'created_at' => $tx->created_at?->diffForHumans(),
                ]);
            }
            foreach ($recentProfit as $p) {
                $recentActivity->push([
                    'action' => 'Month Finalized',
                    'entity_type' => 'MonthlyProfitSummary',
                    'user' => '—',
                    'created_at' => $p->updated_at?->diffForHumans(),
                ]);
            }
            $recentActivity = $recentActivity->take(10);
        }

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
                    'value' => $totalInvestment,
                    'tone' => 'primary',
                    'hint' => "{$activeInvestors} active investors",
                    'icon' => 'Wallet',
                ],
                [
                    'label' => 'Current Month Profit',
                    'value' => $currentMonthProfit,
                    'tone' => 'success',
                    'hint' => date('F, Y', strtotime($currentMonth)),
                    'icon' => 'TrendingUp',
                ],
                [
                    'label' => 'M / Y Profit',
                    'value' => $myProfit,
                    'tone' => 'accent',
                    'hint' => $myRatio > 0 ? "{$myRatio}% ratio" : 'No data',
                    'icon' => 'CircleDollarSign',
                ],
                [
                    'label' => 'Active Investors',
                    'value' => (float) $activeInvestors,
                    'tone' => 'info',
                    'hint' => "of {$totalInvestors} total",
                    'icon' => 'Users',
                ],
            ],
            'trend' => $trendMonths,
            'sectorAllocation' => $sectorAllocation,
            'tierDistribution' => [
                ['name' => 'Tier 100%', 'value' => $tier100, 'color' => '#10B981'],
                ['name' => 'Tier 80%',  'value' => $tier80,  'color' => '#F59E0B'],
                ['name' => 'Tier 60%',  'value' => $tier60,  'color' => '#06B6D4'],
            ],
            'recentActivity' => $recentActivity->values(),
            'hasData' => $totalInvestment > 0 || $currentMonthProfit > 0,
        ]);
    }
}
