<?php

namespace App\Http\Controllers;

use App\Exports\InvestmentProfitExport;
use App\Models\Director;
use App\Models\DirectorTransaction;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\ProfitAdjustment;
use App\Models\Sector;
use App\Models\SectorInvestment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PDF Exports
    |--------------------------------------------------------------------------
    */

    /**
     * Export investor ledger as PDF.
     */
    public function investorLedgerPdf(Request $request)
    {
        $investor = Investor::with(['dueLedger', 'profitDueLedger'])->findOrFail($request->investor_id);

        $transactions = InvestmentTransaction::where('investor_id', $investor->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('transaction_date', '<=', $d))
            ->orderBy('transaction_date')->get();

        $profitDetails = InvestorMonthlyProfitDetail::where('investor_id', $investor->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('profit_month', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('profit_month', '<=', $d))
            ->orderBy('profit_month')->get();

        $adjustments = ProfitAdjustment::where('investor_id', $investor->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('transaction_date', '<=', $d))
            ->orderBy('transaction_date')->get();

        $pdf = Pdf::loadView('exports.investor-ledger', [
            'investor' => $investor,
            'transactions' => $transactions,
            'profitDetails' => $profitDetails,
            'adjustments' => $adjustments,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);

        $filename = 'investor-ledger-'.str()->slug($investor->name).'-'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export sector ledger as PDF.
     */
    public function sectorLedgerPdf(Request $request)
    {
        $sector = Sector::with(['dueLedger', 'profitDueLedger'])->findOrFail($request->sector_id);

        $investments = SectorInvestment::where('sector_id', $sector->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('transaction_date', '<=', $d))
            ->orderBy('transaction_date')->get();

        $profits = MonthlySectorProfit::where('sector_id', $sector->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('profit_month', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('profit_month', '<=', $d))
            ->orderBy('profit_month')->get();

        $adjustments = ProfitAdjustment::where('sector_id', $sector->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('transaction_date', '<=', $d))
            ->orderBy('transaction_date')->get();

        $pdf = Pdf::loadView('exports.sector-ledger', [
            'sector' => $sector,
            'investments' => $investments,
            'profits' => $profits,
            'adjustments' => $adjustments,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);

        $filename = 'sector-ledger-'.str()->slug($sector->name).'-'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export M/Y ledger as PDF.
     */
    public function myLedgerPdf(Request $request)
    {
        $director = Director::with('dueLedger')->findOrFail($request->director_id);

        $transactions = DirectorTransaction::where('director_id', $director->id)
            ->when($request->date_from, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('transaction_date', '<=', $d))
            ->orderBy('transaction_date')->get();

        $summaries = MonthlyProfitSummary::when($request->date_from, fn ($q, $d) => $q->where('profit_month', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('profit_month', '<=', $d))
            ->orderBy('profit_month')->get();

        $pdf = Pdf::loadView('exports.my-ledger', [
            'director' => $director,
            'transactions' => $transactions,
            'summaries' => $summaries,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);

        $filename = 'my-ledger-'.str()->slug($director->name).'-'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /*
    |--------------------------------------------------------------------------
    | Excel Exports
    |--------------------------------------------------------------------------
    */

    /**
     * Export investment profit report as Excel — the "For Sajid" sheet replica.
     */
    public function investmentProfitExcel(Request $request)
    {
        $month = $request->get('month', date('Y-m-01'));
        $month = date('Y-m-01', strtotime($month));

        $details = InvestorMonthlyProfitDetail::where('profit_month', $month)
            ->with('investor:id,name,reference,deed_ratio')
            ->orderByDesc('investment')
            ->get();

        $summary = MonthlyProfitSummary::find($month);

        $export = new InvestmentProfitExport($details, $summary, $month);

        $monthLabel = date('F_Y', strtotime($month));
        $filename = "investment-profit-{$monthLabel}.xlsx";

        return Excel::download($export, $filename);
    }
}
