<?php

namespace App\Http\Controllers;

use App\Enums\InvestmentType;
use App\Enums\SectorProfitStatus;
use App\Exports\AutoCalcTemplateExport;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\MonthlySectorProfit;
use App\Models\RetainedEarnings;
use App\Models\Sector;
use App\Models\SectorInvestment;
use App\Services\ProfitCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AutoCalcController extends Controller
{
    public function __construct(
        private readonly ProfitCalculatorService $profitCalculator,
    ) {}

    /**
     * Display the Auto Calculation page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('AutoCalc/Index', [
            'month' => $request->get('month', date('Y-m-01')),
        ]);
    }

    /**
     * Download the Excel template pre-filled with current data.
     */
    public function downloadTemplate(Request $request)
    {
        $month = $request->get('month', date('Y-m-01'));
        $month = date('Y-m-01', strtotime($month));

        $export = new AutoCalcTemplateExport($month);
        $monthLabel = date('F_Y', strtotime($month));
        $filename = "auto-calc-template-{$monthLabel}.xlsx";

        return Excel::download($export, $filename);
    }

    /**
     * Upload the filled Excel template and process it.
     *
     * Parses each sheet, creates all transactions, runs the 8-phase engine.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'month' => ['required', 'date'],
        ]);

        $month = date('Y-m-01', strtotime($request->input('month')));
        $userId = $request->user()->id;
        $file = $request->file('file');

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getRealPath());

            $results = [
                'investors_processed' => 0,
                'investors_skipped' => 0,
                'sectors_processed' => 0,
                'sectors_skipped' => 0,
                'retained_earnings_set' => false,
                'calculation_run' => false,
                'errors' => [],
            ];

            // ── 1. Process Investors sheet ────────────────────────────
            $investorSheet = $spreadsheet->getSheetByName('Investors');
            if (!$investorSheet) {
                return redirect()->back()->with('error', 'Sheet "Investors" not found in the uploaded file.');
            }
            $investorData = $investorSheet->toArray();
            // Skip header row (row 1)
            foreach (array_slice($investorData, 1) as $row) {
                $name = trim($row[0] ?? '');
                $newInvestment = (float) ($row[4] ?? 0);

                if (empty($name) || $newInvestment <= 0) {
                    $results['investors_skipped']++;
                    continue;
                }

                // Find investor by name
                $investor = Investor::where('name', $name)->first();
                if (!$investor) {
                    $results['errors'][] = "Investor '{$name}' not found — skipped";
                    $results['investors_skipped']++;
                    continue;
                }

                // Create investment transaction
                $tx = InvestmentTransaction::create([
                    'investor_id' => $investor->id,
                    'amount' => $newInvestment,
                    'type' => InvestmentType::Add,
                    'transaction_month' => $month,
                    'transaction_date' => $month,
                    'remarks' => 'Auto-calc upload',
                    'created_by' => $userId,
                ]);

                // Update due ledger
                $tx->updateDue($investor->id, $tx->signedAmount(), $month);
                $results['investors_processed']++;
            }

            // ── 2. Process Sectors sheet ─────────────────────────────
            $sectorSheet = $spreadsheet->getSheetByName('Sectors');
            if (!$sectorSheet) {
                return redirect()->back()->with('error', 'Sheet "Sectors" not found in the uploaded file.');
            }
            $sectorData = $sectorSheet->toArray();
            $sectorProfits = [];
            foreach (array_slice($sectorData, 1) as $row) {
                $name = trim($row[0] ?? '');
                $newAllocation = (float) ($row[2] ?? 0);
                $estimated = (float) ($row[3] ?? 0);
                $actual = (float) ($row[4] ?? 0);

                if (empty($name)) {
                    continue;
                }

                $sector = Sector::where('name', $name)->first();
                if (!$sector) {
                    $results['errors'][] = "Sector '{$name}' not found — skipped";
                    $results['sectors_skipped']++;
                    continue;
                }

                // Create sector investment if allocation > 0
                if ($newAllocation > 0) {
                    $inv = SectorInvestment::create([
                        'sector_id' => $sector->id,
                        'amount' => $newAllocation,
                        'type' => InvestmentType::Add,
                        'transaction_date' => $month,
                        'remarks' => 'Auto-calc allocation',
                        'created_by' => $userId,
                    ]);
                    $inv->updateDue($sector->id, $inv->signedAmount(), $month);
                }

                // Create monthly sector profit if estimated or actual > 0
                if ($estimated > 0 || $actual > 0) {
                    MonthlySectorProfit::updateOrCreate(
                        ['sector_id' => $sector->id, 'profit_month' => $month],
                        [
                            'estimated_profit' => $estimated,
                            'actual_profit' => $actual,
                            'status' => SectorProfitStatus::Finalized,
                            'transaction_date' => now(),
                            'finalized_by' => $userId,
                            'finalized_at' => now(),
                            'created_by' => $userId,
                        ],
                    );
                    $results['sectors_processed']++;
                } else {
                    $results['sectors_skipped']++;
                }
            }

            // ── 3. Process Retained Earnings sheet ───────────────────
            $reSheet = $spreadsheet->getSheetByName('Retained Earnings');
            $reTotal = 200000.0;
            $reInvestorPct = 71.0;
            $reMyPct = 29.0;

            if ($reSheet) {
                $reData = $reSheet->toArray();
                // Row 2: Month, Row 3: Total Amount, Row 4: Investor %, Row 5: M/Y %
                $reTotal = (float) ($reData[2][1] ?? 200000);
                $reInvestorPct = (float) ($reData[3][1] ?? 71);
                $reMyPct = (float) ($reData[4][1] ?? 29);
                $results['retained_earnings_set'] = true;
            }

            // ── 4. Run the 8-phase calculation engine ─────────────────
            // Only if at least one sector has profit data
            $hasProfits = MonthlySectorProfit::where('profit_month', $month)
                ->where(function ($q) {
                    $q->where('estimated_profit', '>', 0)
                      ->orWhere('actual_profit', '>', 0);
                })
                ->exists();

            if ($hasProfits) {
                $calcResult = $this->profitCalculator->calculate(
                    $month,
                    $userId,
                    $reTotal,
                    $reInvestorPct,
                    $reMyPct,
                );

                $results['calculation_run'] = true;
                $results['summary'] = $calcResult['summary'];
                $results['details_count'] = $calcResult['details_count'];
            }

            Log::info('Auto-calc upload processed', $results);

            return redirect()
                ->route('auto-calc.index')
                ->with('success', 'Auto-calculation completed successfully!')
                ->with('results', $results);

        } catch (\Exception $e) {
            Log::error('Auto-calc upload failed', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }
}
