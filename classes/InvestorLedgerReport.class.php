<?php 
class InvestorLedgerReport extends Dbh {
    use SharedFunctionalityTrait;

protected function InvestorWiseMerged($date_from, $date_to, $investor_id) {
    $conn = $this->connect();

    if ($date_from == '' || $date_to == '' || $investor_id == '') {
        return '<b class="text-danger">Fill all data</b>';
    }

    $investor = (new NewInvestor())->SingleData($investor_id);
    $ReportName = "{$investor['name']} :: Profit Report From $date_from to $date_to";

    // Normalize dates
    $date_from_sql = DateTime::createFromFormat('m/d/Y', $date_from)->format('Y-m-d');
    $date_to_sql   = DateTime::createFromFormat('m/d/Y', $date_to)->format('Y-m-d');
    $cutoffMonth   = (new DateTime($date_from_sql))->modify('first day of previous month')->format('Y-m');

    // ======================================================
    // Step 1: Calculate Previous Balance
    // ======================================================
   
    $data = New InvestorProfitDueManager();
    $previousBalance = $data->getPreviousDueByDate($investor_id,$date_from); 


    // ======================================================
    // Step 2: Fetch Monthly Profit Data (within range)
    // ======================================================
    $stmt = $conn->prepare("
        SELECT *
        FROM investor_monthly_profit_details
        WHERE investor_id = :investor_id
          AND transaction_date BETWEEN :date_from AND :date_to
        ORDER BY transaction_date ASC
    ");
    $stmt->execute([
        ':investor_id' => $investor_id,
        ':date_from'   => $date_from_sql,
        ':date_to'     => $date_to_sql
    ]);
    $profitRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index profit data by month
    $dataByMonth = [];
    foreach ($profitRows as $row) {
        $monthKey = $row['month'];
        if (!isset($dataByMonth[$monthKey])) {
            $dataByMonth[$monthKey] = ['profit' => [], 'advance' => []];
        }
        $dataByMonth[$monthKey]['profit'][] = $row;
    }

    // Advance adjustments within range
    $stmt = $conn->prepare("
        SELECT transaction_date, amount, month as transaction_month
        FROM investor_advance_profit_adjustment
        WHERE investor_id = :investor_id AND 
          
           transaction_date BETWEEN :date_from AND :date_to
        ORDER BY transaction_date ASC
    ");
    $stmt->execute([
        ':investor_id' => $investor_id,
        ':date_from'   => $date_from_sql,
        ':date_to'     => $date_to_sql
    ]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $adv) {
        $monthKey = $adv['transaction_month'];
        if (!isset($dataByMonth[$monthKey])) {
            $dataByMonth[$monthKey] = ['profit' => [], 'advance' => []];
        }
        $dataByMonth[$monthKey]['advance'][] = $adv;
    }

    ksort($dataByMonth); // sort months

    // ======================================================
    // Step 3: Totals (current period only)
    // ======================================================
    $total_estimated    = 0;
    $total_actual       = 0;
    $total_deed_ratio   = 0;
    $total_final_profit = 0;
    $total_advance_paid = 0;
    $total_advance_adj  = 0;

    // ======================================================
    // Step 4: Render HTML
    // ======================================================
    $content = "<div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>{$ReportName}</h3></div>
        <div class='card-body'><div class='table-responsive'>
        <table class='table table-bordered'>
            <thead>";
                // ---- Previous Balance Row ----
    $content .= "<tr class='table-warning'>
        <td colspan='7'><b>Previous Balance (till {$cutoffMonth})</b></td>
        <td><b>" . number_format($previousBalance, 2) . "</b></td>
    </tr>";


                $content .= "<tr>
                    <th>Month</th>
                    <th>Date</th>
                    <th>Disbursement Profit</th>
                    <th>Actual Profit</th>
                    <th>Deed %</th>
                    <th>Profit as per deed %</th>
                    <th>Advance Paid</th>
                    <th>Adv. Profit Adjustment</th>
                </tr>
            </thead>
            <tbody>";


    // ---- Month-wise Rows ----
    foreach ($dataByMonth as $monthKey => $entries) {
        $monthName = date("M Y", strtotime($monthKey . "-01"));
        $rowCount  = count($entries['profit']) + count($entries['advance']);
        $firstRow  = true;

        // Profit entries
        foreach ($entries['profit'] as $p) {
            $content .= "<tr>";
            if ($firstRow) {
                $content .= "<td rowspan='{$rowCount}'>{$monthName}</td>";
                $firstRow = false;
            }
            $content .= "
                <td>" . date('jS F, Y', strtotime($p['month'].'-01')) . "</td>
                <td>" . number_format((float)$p['estimated_profit'], 2) . "</td>
                <td>" . number_format((float)$p['actual_profit_before_deed'], 2) . "</td>
                <td>{$p['deed_ratio']}</td>
                <td>" . number_format((float)$p['final_profit'], 2) . "</td>
                <td>" . number_format((float)$p['advance_paid'], 2) . "</td>
                <td>-</td>
            </tr>";

            $total_estimated    += (float)$p['estimated_profit'];
            $total_actual       += (float)$p['actual_profit_before_deed'];
            $total_deed_ratio   += (float)$p['deed_ratio'];
            $total_final_profit += (float)$p['final_profit'];
            $total_advance_paid += (float)$p['advance_paid'];
        }

        // Advance adjustments
        foreach ($entries['advance'] as $a) {
            $content .= "<tr>";
            if ($firstRow) {
                $content .= "<td rowspan='{$rowCount}'>{$monthName}</td>";
                $firstRow = false;
            }
            $content .= "
                <td>" . date('jS F, Y', strtotime($a['transaction_date'])) . "</td>
                <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
                <td>" . number_format((float)$a['amount'], 2) . "</td>
            </tr>";

            $total_advance_adj += (float)$a['amount'];
        }
    }

    // ---- Footer Totals ----
   // $finalBalance = $previousBalance + $total_actual - ($total_advance_paid + $total_advance_adj);

 $finalBalance =   ($total_advance_paid - $total_advance_adj)  ;

    $content .= "</tbody>
        <tfoot>
            <tr>
                <th colspan='2'>Total (Current Period)</th>
                <th>" . number_format($total_estimated, 2) . "</th>
                <th>" . number_format($total_actual, 2) . "</th>
                <th></th>
                <th>" . number_format($total_final_profit, 2) . "</th>
                <th>" . number_format($total_advance_paid, 2) . "</th>
                <th>" . number_format($total_advance_adj, 2) . "</th>
            </tr>
            <tr>
                <th class='text-danger'>Final Balance</th>
                <th>" . number_format($finalBalance, 2) . "</th>
                <th colspan='6'></th>
            </tr>
        </tfoot>
        </table></div></div></div>";

    return $content;
}





  
}
