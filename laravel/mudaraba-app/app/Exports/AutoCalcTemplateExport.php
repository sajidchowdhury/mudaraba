<?php

namespace App\Exports;

use App\Models\Investor;
use App\Models\Sector;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * AutoCalcTemplateExport — generates a 4-sheet Excel template for the
 * "Auto Calculation" bulk upload feature.
 *
 * Sheets:
 *   1. Instructions — what to fill in, what not to touch
 *   2. Investors — pre-filled with name/ref/tier/balance, blank: New Investment
 *   3. Sectors — pre-filled with name/balance, blank: New Allocation, Estimated, Actual
 *   4. Retained Earnings — blank: Total Amount, Investor %, M/Y %
 */
class AutoCalcTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly string $month,
    ) {}

    public function sheets(): array
    {
        return [
            new InstructionsSheet($this->month),
            new InvestorsSheet(),
            new SectorsSheet(),
            new RetainedEarningsSheet($this->month),
        ];
    }
}

// ============================================================================
// Sheet 1: Instructions
// ============================================================================

class InstructionsSheet implements FromArray, WithTitle, WithColumnWidths, WithStyles
{
    public function __construct(private readonly string $month) {}

    public function title(): string { return 'Instructions'; }

    public function array(): array
    {
        $monthLabel = date('F, Y', strtotime($this->month));
        return [
            ["Mudaraba Auto-Calculation Template — {$monthLabel}"],
            [''],
            ['HOW TO USE THIS TEMPLATE:'],
            ['1. Go to the "Investors" sheet — fill in "New Investment" for each investor'],
            ['   (leave 0 if the investor has no new investment this month)'],
            [''],
            ['2. Go to the "Sectors" sheet — fill in:'],
            ['   - "New Allocation": new funds to deploy to this sector (0 if none)'],
            ['   - "Estimated Profit": budgeted profit for the month (Z column)'],
            ['   - "Actual Profit": realized profit for the month (X column)'],
            [''],
            ['3. Go to the "Retained Earnings" sheet — enter:'],
            ['   - Total Amount: how much to set aside as retained earnings (e.g. 200000)'],
            ['   - Investor %: investor portion (default 71)'],
            ['   - M/Y %: M/Y portion (default 29, must sum to 100 with investor %)'],
            [''],
            ['4. Save the file and upload it via the Auto Calculation page.'],
            ['   The system will automatically:'],
            ['   - Create investment transactions for all investors with new investments'],
            ['   - Create sector investments for all sectors with new allocations'],
            ['   - Create monthly sector profits (estimated + actual)'],
            ['   - Set retained earnings'],
            ['   - Run the 8-phase calculation engine'],
            ['   - Show you the results (M/Y profit, investor totals, etc.)'],
            [''],
            ['IMPORTANT:'],
            ['- Do NOT change the "Name", "Reference", "Tier", or "Previous Balance" columns'],
            ['- Do NOT rename the sheets'],
            ['- Do NOT add or delete rows — only fill in the blank columns'],
            ['- The "Previous Balance" columns show the current balance (read-only reference)'],
        ];
    }

    public function columnWidths(): array { return ['A' => 80]; }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '10B981']]],
            3 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

// ============================================================================
// Sheet 2: Investors
// ============================================================================

class InvestorsSheet implements FromArray, WithTitle, WithColumnWidths, WithStyles
{
    public function title(): string { return 'Investors'; }

    public function array(): array
    {
        $rows = [
            ['Name', 'Reference', 'Tier', 'Previous Balance', 'New Investment'],
        ];
        $investors = Investor::with('dueLedger')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        foreach ($investors as $inv) {
            $rows[] = [
                $inv->name,
                $inv->reference ?? '',
                (float) $inv->deed_ratio,
                (float) ($inv->dueLedger?->due ?? 0),
                0,
            ];
        }
        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 15, 'C' => 8, 'D' => 18, 'E' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '10B981']],
            ],
        ];
    }
}

// ============================================================================
// Sheet 3: Sectors
// ============================================================================

class SectorsSheet implements FromArray, WithTitle, WithColumnWidths, WithStyles
{
    public function title(): string { return 'Sectors'; }

    public function array(): array
    {
        $rows = [
            ['Name', 'Previous Balance', 'New Allocation', 'Estimated Profit', 'Actual Profit'],
        ];
        $sectors = Sector::with('dueLedger')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        foreach ($sectors as $sec) {
            $rows[] = [
                $sec->name,
                (float) ($sec->dueLedger?->due ?? 0),
                0,
                0,
                0,
            ];
        }
        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '10B981']],
            ],
        ];
    }
}

// ============================================================================
// Sheet 4: Retained Earnings
// ============================================================================

class RetainedEarningsSheet implements FromArray, WithTitle, WithColumnWidths, WithStyles
{
    public function __construct(private readonly string $month) {}

    public function title(): string { return 'Retained Earnings'; }

    public function array(): array
    {
        return [
            ['Field', 'Value', 'Notes'],
            ['Month', $this->month, 'YYYY-MM-DD format (1st of month) — do NOT change'],
            ['Total Amount', 200000, 'Total retained earnings for this month (BDT)'],
            ['Investor %', 71, 'Investor portion (must sum to 100 with M/Y %)'],
            ['M/Y %', 29, 'M/Y portion (must sum to 100 with Investor %)'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 18, 'C' => 50];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '10B981']],
            ],
        ];
    }
}
