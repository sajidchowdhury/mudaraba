<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvestmentProfitExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $details,
        private readonly ?object $summary,
        private readonly string $month,
    ) {}

    public function title(): string
    {
        return date('F, Y', strtotime($this->month));
    }

    public function collection(): Collection
    {
        return $this->details;
    }

    public function headings(): array
    {
        return [
            'Investor', 'Reference', 'Tier',
            'Investment (D)', 'Ratio (E)', 'Primary Share (Q)',
            'Actual @100% (N)', 'Deed Ratio (AF)', 'Profit Due (AG)',
            'Advance Diff (AH)', 'Retained Credit (AJ)', 'Net Settlement (AK)',
        ];
    }

    public function map($detail): array
    {
        return [
            $detail->investor?->name ?? '—',
            $detail->investor?->reference ?? '',
            $detail->investor?->deed_ratio ?? $detail->deed_ratio,
            (float) $detail->investment,
            (float) $detail->investment_ratio,
            (float) $detail->primary_profit_share,
            (float) $detail->actual_profit_at_full,
            (float) $detail->deed_ratio,
            (float) $detail->actual_profit_due,
            (float) $detail->advance_difference,
            (float) $detail->retained_earnings_credit,
            (float) $detail->net_settlement,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, 'B' => 15, 'C' => 8,
            'D' => 18, 'E' => 12, 'F' => 18,
            'G' => 18, 'H' => 12, 'I' => 18,
            'J' => 18, 'K' => 18, 'L' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '10B981']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $sheet->getHighestDataRow() + 2;

                $sheet->setCellValue("A{$lastRow}", 'TOTALS');
                $sheet->setCellValue("D{$lastRow}", $this->summary?->total_mudaraba_investment ?? 0);
                $sheet->setCellValue("I{$lastRow}", $this->summary?->total_investor_profit_due ?? 0);
                $sheet->setCellValue("J{$lastRow}", $this->summary?->total_investor_advance_diff ?? 0);
                $sheet->setCellValue("K{$lastRow}", $this->summary?->total_investor_retained ?? 0);
                $sheet->setCellValue("L{$lastRow}", $this->summary?->my_profit ?? 0);

                $sheet->getStyle("A{$lastRow}:L{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$lastRow}:L{$lastRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D1FAE5');

                $ratioRow = $lastRow + 1;
                $sheet->setCellValue("A{$ratioRow}", 'M/Y Profit Ratio (AG186):');
                $sheet->setCellValue("B{$ratioRow}", ($this->summary?->my_profit_ratio ?? 0).'%');
                $sheet->getStyle("A{$ratioRow}")->getFont()->setBold(true);

                for ($col = 'D'; $col <= 'L'; $col++) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
