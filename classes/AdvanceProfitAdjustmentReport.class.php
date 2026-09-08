<?php 
class AdvanceProfitAdjustmentReport extends Dbh {
    use SharedFunctionalityTrait;


    use SharedFunctionalityTrait;

protected function InvestorWiseAdvanceReport($date_from, $date_to, $investor_id) {
    $conn = $this->connect();

    if ($date_from == '' || $date_to == '' || $investor_id == '') {
        return '<b class="text-danger">Fill all data</b>';
    }

    $date_from = DateTime::createFromFormat('m/d/Y', $date_from)->format('Y-m-d');
    $date_to   = DateTime::createFromFormat('m/d/Y', $date_to)->format('Y-m-d');

    // Investor info
    $investor = (new NewInvestor())->SingleData($investor_id);
    $reportTitle = "{$investor['name']} :: Advance Profit Report From $date_from to $date_to";

    // Total Advance Given (A)
    $stmt = $conn->prepare("
        SELECT SUM(advance_paid) as total_adv
        FROM investor_monthly_profit_details
        WHERE investor_id = :investor_id
          AND transaction_date BETWEEN :date_from AND :date_to
    ");
    $stmt->execute([
        ':investor_id' => $investor_id,
        ':date_from'   => $date_from,
        ':date_to'     => $date_to
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalA = (float)$row['total_adv'];

    // Total Adjustments (B)
    $stmt = $conn->prepare("
        SELECT SUM(amount) as total_adj
        FROM investor_advance_profit_adjustment
        WHERE investor_id = :investor_id
          AND transaction_date BETWEEN :date_from AND :date_to
    ");
    $stmt->execute([
        ':investor_id' => $investor_id,
        ':date_from'   => $date_from,
        ':date_to'     => $date_to
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalB = (float)$row['total_adj'];

    $balance = $totalA - $totalB;

    // HTML
    $content = "
    <div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>{$reportTitle}</h3></div>
        <div class='card-body'>
            <div class='table-responsive'>
                <table class='table table-bordered'>
                    <thead>
                        <tr>
                            <th>Investor</th>
                            <th>Total Adv. Profit Given (A)</th>
                            <th>Total Profit Adjustment (B)</th>
                            <th>Balance (A-B)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>".htmlspecialchars($investor['name'])."</td>
                            <td>".number_format($totalA, 2)."</td>
                            <td>".number_format($totalB, 2)."</td>
                            <td>".number_format($balance, 2)."</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>";

    return $content;
}




protected function AllInvestorsAdvanceReport($date_from, $date_to) {
    $conn = $this->connect();

    if ($date_from == '' || $date_to == '') {
        return '<b class="text-danger">Fill all data</b>';
    }

    $date_from = DateTime::createFromFormat('m/d/Y', $date_from)->format('Y-m-d');
    $date_to   = DateTime::createFromFormat('m/d/Y', $date_to)->format('Y-m-d');

    $reportTitle = "All Investors :: Advance Profit Report From $date_from to $date_to";

    // Query investor-wise totals
    $stmt = $conn->prepare("
        SELECT inv.id as investor_id, inv.name,
               COALESCE(SUM(d.advance_paid),0) as totalA,
               COALESCE((
                   SELECT SUM(a.amount) 
                   FROM investor_advance_profit_adjustment a 
                   WHERE a.investor_id = inv.id 
                     AND a.transaction_date BETWEEN :date_from AND :date_to
               ),0) as totalB
        FROM investors inv
        LEFT JOIN investor_monthly_profit_details d 
               ON d.investor_id = inv.id
              AND d.transaction_date BETWEEN :date_from AND :date_to
        GROUP BY inv.id, inv.name
        ORDER BY inv.name ASC
    ");
    $stmt->execute([
        ':date_from' => $date_from,
        ':date_to'   => $date_to
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grandA = $grandB = 0;
    $content = "
    <div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>{$reportTitle}</h3></div>
        <div class='card-body'>
            <div class='table-responsive'>
                <table class='table table-bordered'>
                    <thead>
                        <tr>
                            <th>Investor</th>
                            <th>Total Adv. Profit Given</th>
                            <th>Total Profit Adjustment</th>
                            <th>Balance </th>
                        </tr>
                    </thead>
                    <tbody>";

    foreach ($rows as $r) {
        $balance = $r['totalA'] - $r['totalB'];
        $grandA += $r['totalA'];
        $grandB += $r['totalB'];

        $content .= "<tr>
            <td>".htmlspecialchars($r['name'])."</td>
            <td>".number_format($r['totalA'], 2)."</td>
            <td>".number_format($r['totalB'], 2)."</td>
            <td>".number_format($balance, 2)."</td>
        </tr>";
    }

    $content .= "</tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>".number_format($grandA, 2)."</th>
                <th>".number_format($grandB, 2)."</th>
                <th>".number_format($grandA - $grandB, 2)."</th>
            </tr>
        </tfoot>
        </table>
    </div></div></div>";

    return $content;
}




  
}
