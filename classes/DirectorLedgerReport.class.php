<?php 
class DirectorLedgerReport extends Dbh {
    use SharedFunctionalityTrait;

   public function MonthWiseReceivable($date_from, $date_to) {
    $conn = $this->connect();

    if ($date_from == '' || $date_to == '') {
        return '<b class="text-danger">Select date range</b>';
    }

    $date_from = DateTime::createFromFormat('m/d/Y', $date_from)->format('Y-m-d');
    $date_to   = DateTime::createFromFormat('m/d/Y', $date_to)->format('Y-m-d');

    // --- Step 1: Get month list ---
    $months = [];
    $start  = new DateTime($date_from);
    $end    = new DateTime($date_to);
    $end->modify('first day of next month'); 

    while ($start < $end) {
        $months[] = $start->format('Y-m');
        $start->modify('+1 month');
    }

    // --- Step 1.1: Get previous month closing balance ---

    $data = New DirectorDueManager();
    $previousBalance = $data->getPreviousDueByDate(1,$date_from); 



    // --- Step 2: Receivable (monthly_profit_summary) ---
    $in  = str_repeat('?,', count($months) - 1) . '?';
    $sql = "SELECT month, SUM(my_amount) as total_receivable
            FROM monthly_profit_summary
            WHERE month IN ($in)
            GROUP BY month";
    $stmt = $conn->prepare($sql);
    $stmt->execute($months);
    $receivableRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // --- Step 3: Adjustments (director_transactions) ---
    $sql = "SELECT amount, transaction_date
            FROM director_transactions
            WHERE transaction_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$date_from, $date_to]);
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAdjusted = 0;
    $adjDates = [];
    foreach ($adjustments as $adj) {
        $totalAdjusted += $adj['amount'];
        $adjDates[] = date("jS M", strtotime($adj['transaction_date']));
    }
    $adjDatesStr = !empty($adjDates) ? "(on " . implode(", ", $adjDates) . ")" : "";

    // --- Step 4: Build arrays ---
    $receivableData = [];
    $totalReceivable = 0;

    foreach ($months as $m) {
        $r = $receivableRows[$m] ?? 0;
        $receivableData[$m] = $r;
        $totalReceivable += $r;
    }


$prevMonth = (new DateTime($date_from))->modify('first day of previous month')->format('Y-m');
$totalReceivable = $totalReceivable+$previousBalance;
$balance = $totalReceivable - $totalAdjusted;

    // --- Step 5: Render HTML ---
    $content = "<div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>Profit Receivable Report ($date_from to $date_to)</h3></div>
        <div class='card-body'><div class='table-responsive'>
        <table class='table table-bordered text-center'>
            <thead>
                <tr>
                    <th>Previous Balance<br>($prevMonth)</th>";

    foreach ($months as $m) {
        $monthName = date("F", strtotime($m . "-01"));
        $content .= "<th>{$monthName}</th>";
    }
    $content .= "<th class='text-danger'>Total Profit <br>Receivable</th>
                 <th class='text-danger'>Receivable Adjusted <br>$adjDatesStr</th>
                 <th class='text-danger'>Balance</th></tr></thead><tbody>";

    // Row 1: Receivable
    $content .= "<tr>";
    $content .= "<td><b>" . number_format($previousBalance, 2) . "</b></td>";

    foreach ($months as $m) {
        $content .= "<td>" . number_format($receivableData[$m], 2) . "</td>";
    }

    $content .= "<td><b class='text-danger'>" . number_format($totalReceivable, 2) . "</b></td>";
    $content .= "<td><b class='text-danger'>" . number_format($totalAdjusted, 2) . "</b></td>";
    $content .= "<td><b class='text-danger'>" . number_format($balance, 2) . "</b></td></tr>";

    $content .= "</tbody></table></div></div></div>";

    return $content;
}



    protected function AllDirector($date_from, $date_to) { 
        $conn = $this->connect();
         $ReportName = "M/Y Ledger Report :: From $date_from To $date_to";

    $date_from = DateTime::createFromFormat('m/d/Y', $date_from)->format('Y-m-d');
    $date_to   = DateTime::createFromFormat('m/d/Y', $date_to)->format('Y-m-d');


        $ledger = [];

        // ---- Profits in range ----
         $stmt = $conn->prepare("
            SELECT month, my_amount,transaction_date
            FROM monthly_profit_summary
            WHERE  transaction_date BETWEEN :date_from AND :date_to
            ORDER BY month ASC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to'   => $date_to
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
  
            $ledger[] = [
                'date' => $p['transaction_date'], // normalize to first day
                'description' => 'M/Y',
                'my_profit' => (float) $p['my_amount'],
                'withdraw_profit' => 0
            ];
        }

          // ---- Profits in range ----
        $stmt = $conn->prepare("
            SELECT A.transaction_month, A.amount,B.name ,A.transaction_date
            FROM director_transactions A
            JOIN directors B ON (A.director_id = B.id)
            WHERE A.transaction_date BETWEEN :date_from AND :date_to
            ORDER BY A.transaction_date ASC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to'   => $date_to
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
  
            $ledger[] = [
                'date' => $p['transaction_date'] , // normalize to first day
                'description' => 'Withdraw',
                'my_profit' =>0,
                'withdraw_profit' =>  (float) $p['amount']
            ];
        }


        return $this->renderLedgerTable($ledger, $ReportName, 0);
    }

 private function renderLedgerTable(array $ledger, string $ReportName, float $previous_balance) {


    $totals = ['my_profit' => 0.0, 'withdraw_profit' => 0.0];

    // Group entries by month
    $dataByMonth = [];
    foreach ($ledger as $entry) {
        $monthKey = date('Y-m', strtotime($entry['date'])); // e.g. 2025-09
        $dataByMonth[$monthKey][] = $entry;
    }

    $content = "<div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>{$ReportName}</h3></div>
        <div class='card-body'><div class='table-responsive'>
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>M/Y</th>
                    <th>Withdraw</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($dataByMonth as $monthKey => $entries) {
        $monthName = date("M Y", strtotime($monthKey . "-01"));
        $rowCount = count($entries);
        $firstRow = true;

        foreach ($entries as $e) {
            $totals['my_profit'] += $e['my_profit'];
            $totals['withdraw_profit'] += $e['withdraw_profit'];

            $my_profit = $e['my_profit'] > 0 ? number_format($e['my_profit'], 2) : '-';
            $withdraw_profit = $e['withdraw_profit'] > 0 ? number_format($e['withdraw_profit'], 2) : '-';

            $content .= "<tr>";
            if ($firstRow) {
                $content .= "<td rowspan='{$rowCount}'>{$monthName}</td>";
                $firstRow = false;
            }
            $content .= "
                <td>" . date('jS F, Y', strtotime($e['date'])) . "</td>
                <td>{$e['description']}</td>
                <td>{$my_profit}</td>
                <td>{$withdraw_profit}</td>
            </tr>";
        }
    }

    $profit_balance = $totals['my_profit'] - $totals['withdraw_profit'];
    $color = $profit_balance >= 0 ? 'green' : 'red';

    $content .= "</tbody><tfoot>
        <tr>
            <th></th>
            <th></th>
            <th>Total</th>
            <th>" . number_format($totals['my_profit'], 2) . "</th>
            <th>" . number_format($totals['withdraw_profit'], 2) . "</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th>Left = " . number_format($profit_balance, 2) . "</th>
            <th></th>
            <th></th>
        </tr>
    </tfoot></table></div></div></div>";

    return $content;
}

}
