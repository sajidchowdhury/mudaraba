<?php 
class SectorLedgerReport extends Dbh {
    use SharedFunctionalityTrait;

    protected function SectorWise($date_from, $date_to, $sector_id) {  
        $conn = $this->connect();
        $sector = (new NewSector())->SingleData($sector_id);
        $ReportName = "{$sector['name']} :: Sector Ledger Report :: From $date_from To $date_to";

        // Convert date format
        $date_from = DateTime::createFromFormat('m/d/Y', $date_from)->format('Y-m-d');
        $date_to   = DateTime::createFromFormat('m/d/Y', $date_to)->format('Y-m-d');

        $previous_balance = 0;
        $ledger = [];

        /** --- FETCH 1: Monthly Sector Profit --- **/
        $stmt1 = $conn->prepare("
            SELECT month, estimated_profit, actual_profit, transaction_date
            FROM monthly_sector_profit
            WHERE sector_id = :sector_id
              AND transaction_date BETWEEN :date_from AND :date_to
            ORDER BY transaction_date ASC
        ");
        $stmt1->execute([
            ':sector_id' => $sector_id,
            ':date_from' => $date_from,
            ':date_to'   => $date_to
        ]);
        $profits = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        /** --- FETCH 2: Advance Profit Adjustment --- **/
        $stmt2 = $conn->prepare("
            SELECT month, amount AS adv_adjustment, transaction_date
            FROM advance_profit_adjustment
            WHERE sector_id = :sector_id
              AND transaction_date BETWEEN :date_from AND :date_to
            ORDER BY transaction_date ASC
        ");
        $stmt2->execute([
            ':sector_id' => $sector_id,
            ':date_from' => $date_from,
            ':date_to'   => $date_to
        ]);
        $advances = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        /** --- Combine Both by Date --- **/
        $combined = [];

        // Add profits
        foreach ($profits as $p) {
            $key = $p['transaction_date'];
            if (!isset($combined[$key])) {
                $combined[$key] = [
                    'date' => $p['transaction_date'],
                    'actual_profit' => 0,
                    'dis_profit' => 0,
                    'adv_adjustment' => 0
                ];
            }
            $combined[$key]['actual_profit'] += (float) $p['actual_profit'];
            $combined[$key]['dis_profit'] += (float) $p['estimated_profit'];
        }

        // Add advances
        foreach ($advances as $a) {
            $key = $a['transaction_date'];
            if (!isset($combined[$key])) {
                $combined[$key] = [
                    'date' => $a['transaction_date'],
                    'actual_profit' => 0,
                    'dis_profit' => 0,
                    'adv_adjustment' => 0
                ];
            }
            $combined[$key]['adv_adjustment'] += (float) $a['adv_adjustment'];
        }

        // Sort by date
        ksort($combined);
        $ledger = array_values($combined);

        return $this->renderLedgerTable($ledger, $ReportName, $previous_balance);
    }

    /** --- Render HTML Table --- **/
    private function renderLedgerTable(array $ledger, string $ReportName, float $previous_balance) {
        $totals = [
            'actual_profit' => 0.0,
            'dis_profit' => 0.0,
            'adv_adjustment' => 0.0
        ];

        $content = "<div class='card card-primary'>
            <div class='card-header'><h3 class='card-title'>{$ReportName}</h3></div>
            <div class='card-body'><div class='table-responsive'>
            <table class='table table-bordered' id='example'>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Actual Profit</th>
                        <th>Disb. Profit</th>
                        <th>Adv. Adjustment</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($ledger as $entry) {
            $totals['actual_profit'] += $entry['actual_profit'];
            $totals['dis_profit'] += $entry['dis_profit'];
            $totals['adv_adjustment'] += $entry['adv_adjustment'];

            $content .= "<tr>
                <td>" . date('jS F, Y', strtotime($entry['date'])) . "</td>
                <td>" . ($entry['actual_profit'] > 0 ? number_format($entry['actual_profit'], 2) : '-') . "</td>
                <td>" . ($entry['dis_profit'] > 0 ? number_format($entry['dis_profit'], 2) : '-') . "</td>
                <td>" . ($entry['adv_adjustment'] > 0 ? number_format($entry['adv_adjustment'], 2) : '-') . "</td>
            </tr>";
        }

        $profit_balance = $totals['dis_profit'] - $totals['actual_profit'] - $totals['adv_adjustment'];
        $color = $profit_balance >= 0 ? 'green' : 'red';

        $content .= "</tbody>
            <tfoot>
                <tr style='font-weight:bold;'>
                    <td>Total</td>
                    <td>" . number_format($totals['actual_profit'], 2) . "</td>
                    <td>" . number_format($totals['dis_profit'], 2) . "</td>
                    <td>" . number_format($totals['adv_adjustment'], 2) . "</td>
                </tr>
                <tr>
                    <td colspan='4' style='color:{$color}; font-weight:bold;'>
                        Profit Balance : " . number_format($profit_balance, 2) . "
                    </td>
                </tr>
            </tfoot>
            </table></div></div></div>";

        return $content;
    }
}
