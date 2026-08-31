<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\MonthlyProfitSummary;
use App\Models\Sector;
use App\Models\SectorInvestment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentMonth = date('Y-m-01');

        // Cache dashboard data for 5 minutes (keyed by month)
        $data = Cache::remember("dashboard:{$currentMonth}", 300, function () use ($currentMonth) {
            // KPI 1: Total investment (single aggregate query)
            $totalInvestment = (float) InvestorDueLedger::sum('due');

            // KPI 2-3: Current month from summary (single query)
            $currentSummary = MonthlyProfitSummary::find($currentMonth);
            $currentMonthProfit = $currentSummary ? (float) $currentSummary->total_actual_profit : 0;
            $myProfit = $currentSummary ? (float) $currentSummary->my_profit : 0;
            $myRatio = $currentSummary ? (float) $currentSummary->my_profit_ratio : 0;

            // KPI 4: Investor counts (2 queries instead of 3 — combine active + total)
            $activeInvestors = Investor::where('status', 'active')->count();
            $totalInvestors = Investor::count();

            // Monthly trend — single query for last 6 months instead of 6 separate finds
            $trendStartDate = date('Y-m-01', strtotime('-5 months'));
            $summaries = MonthlyProfitSummary::where('profit_month', '>=', $trendStartDate)
                ->orderBy('profit_month')
                ->get()
                ->keyBy('profit_month');

            $trendMonths = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m-01', strtotime("-{$i} months"));
                $summary = $summaries->get($date);
                $trendMonths[] = [
                    'month' => $date,
                    'label' => date('M Y', strtotime($date)),
                    'estimated' => $summary ? (float) $summary->total_estimated_profit : 0,
                    'actual' => $summary ? (float) $summary->total_actual_profit : 0,
                    'my_profit' => $summary ? (float) $summary->my_profit : 0,
                ];
            }

            // Sector allocation — single query with eager-loaded dueLedger
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

            // Investor tier distribution — single query with groupBy
            $tierCounts = Investor::selectRaw('deed_ratio, COUNT(*) as count')
                ->where('status', 'active')
                ->groupBy('deed_ratio')
                ->pluck('count', 'deed_ratio');

            // ----- Cash-in-Hand Tracking -----
            // Money collected from investors (InvestmentTransaction) vs deployed to sectors (SectorInvestment).
            // Cash in Hand = net investor deposits − net sector deployments.
            $investorAdds       = (float) InvestmentTransaction::where('type', 'add')->sum('amount');
            $investorWithdraws  = (float) InvestmentTransaction::where('type', 'withdraw')->sum('amount');
            $sectorAdds         = (float) SectorInvestment::where('type', 'add')->sum('amount');
            $sectorWithdraws    = (float) SectorInvestment::where('type', 'withdraw')->sum('amount');

            $totalCollected      = $investorAdds;
            $totalWithdrawn      = $investorWithdraws;
            $totalAllocated      = $sectorAdds;
            $totalSectorReturn   = $sectorWithdraws;
            $netInvestorDeposit  = $investorAdds - $investorWithdraws;
            $netSectorDeployed   = $sectorAdds - $sectorWithdraws;
            $cashInHand          = $netInvestorDeposit - $netSectorDeployed;
            $cashInHandPct       = $netInvestorDeposit > 0 ? ($cashInHand / $netInvestorDeposit) * 100 : 0;

            return [
                'totalInvestment' => $totalInvestment,
                'currentMonthProfit' => $currentMonthProfit,
                'myProfit' => $myProfit,
                'myRatio' => $myRatio,
                'activeInvestors' => $activeInvestors,
                'totalInvestors' => $totalInvestors,
                'trendMonths' => $trendMonths,
                'sectorAllocation' => $sectorAllocation,
                'tier100' => $tierCounts->get('100', 0),
                'tier80' => $tierCounts->get('80', 0),
                'tier60' => $tierCounts->get('60', 0),
                'hasData' => $totalInvestment > 0 || $currentMonthProfit > 0,
                'cashFlow' => [
                    'totalCollected'     => $totalCollected,
                    'totalWithdrawn'      => $totalWithdrawn,
                    'totalAllocated'      => $totalAllocated,
                    'totalSectorReturn'   => $totalSectorReturn,
                    'netInvestorDeposit'  => $netInvestorDeposit,
                    'netSectorDeployed'   => $netSectorDeployed,
                    'cashInHand'          => $cashInHand,
                    'cashInHandPct'       => $cashInHandPct,
                ],
            ];
        });

        // Recent activity — NOT cached (changes frequently, low query cost)
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

        // Fallback to recent transactions if no audit logs
        if ($recentActivity->isEmpty()) {
            $recentTx = InvestmentTransaction::with(['investor:id,name', 'creator:id,username'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
            $recentProfit = MonthlyProfitSummary::orderByDesc('profit_month')->limit(3)->get();

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
                    'value' => $data['totalInvestment'],
                    'tone' => 'primary',
                    'hint' => "{$data['activeInvestors']} active investors",
                    'icon' => 'Wallet',
                ],
                [
                    'label' => 'Current Month Profit',
                    'value' => $data['currentMonthProfit'],
                    'tone' => 'success',
                    'hint' => date('F, Y', strtotime($currentMonth)),
                    'icon' => 'TrendingUp',
                ],
                [
                    'label' => 'M / Y Profit',
                    'value' => $data['myProfit'],
                    'tone' => 'accent',
                    'hint' => $data['myRatio'] > 0 ? "{$data['myRatio']}% ratio" : 'No data',
                    'icon' => 'CircleDollarSign',
                ],
                [
                    'label' => 'Active Investors',
                    'value' => (float) $data['activeInvestors'],
                    'tone' => 'info',
                    'hint' => "of {$data['totalInvestors']} total",
                    'icon' => 'Users',
                ],
            ],
            'trend' => $data['trendMonths'],
            'sectorAllocation' => $data['sectorAllocation'],
            'tierDistribution' => [
                ['name' => 'Tier 100%', 'value' => $data['tier100'], 'color' => '#10B981'],
                ['name' => 'Tier 80%',  'value' => $data['tier80'],  'color' => '#F59E0B'],
                ['name' => 'Tier 60%',  'value' => $data['tier60'],  'color' => '#06B6D4'],
            ],
            'cashFlow' => $data['cashFlow'],
            'recentActivity' => $recentActivity->values(),
            'hasData' => $data['hasData'],
        ]);
    }
}
