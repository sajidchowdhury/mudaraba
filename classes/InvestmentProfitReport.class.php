<?php 
class InvestmentProfitReport extends Dbh {
    use SharedFunctionalityTrait;

   protected function InvestorWiseInvestment($date_from, $investor_id) {  
    $conn = $this->connect();

       if($date_from == '' ||  $investor_id == ''){
            return '<b class="text-danger">Fill all data</b>';

    }


    $investor = (new NewInvestor())->SingleData($investor_id);
    $ReportName = "{$investor['name']} :: Individual Investment Report :: Till $date_from";

 

    // ---- Fetch all transactions in range (ignoring advance & loan) ----
    $stmt = $conn->prepare("
        SELECT transaction_date, type, amount, remarks
        FROM investment_transactions
        WHERE investor_id = :investor_id
          AND transaction_date <= :date_from 
          AND type IN ('add','withdraw')
        ORDER BY transaction_date ASC, id ASC
    ");
    $stmt->execute([
        ':investor_id' => $investor_id,
        ':date_from'   => $date_from
    ]);

    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Build ledger with running balance ----
    $ledger = [];
    $balance = 0;

    foreach ($transactions as $t) {
        if ($t['type'] === 'add') {
            $balance += $t['amount'];
            $desc = "Investment Added";
        } elseif ($t['type'] === 'withdraw') {
            $balance -= $t['amount'];
            $desc = "Withdrawal";
        } else {
            continue; // skip advances/loans just in case
        }

$remarks = trim($t['remarks']);
$remarks = ($remarks === 'N/A' || $remarks === '') ? '' : $remarks;


        $ledger[] = [
            'date' => $t['transaction_date'],
            'description' => $desc . $remarks ,
            'amount' => (float)$t['amount'],
            'balance' => $balance
        ];
    }

    // ---- Render HTML ----
    $content = "<div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>{$ReportName}</h3></div>
        <div class='card-body'><div class='table-responsive'>
        <table class='table table-bordered' id='example'>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($ledger as $entry) {

        $content .= "<tr>
            <td>" . date('jS F, Y', strtotime($entry['date'])) . "</td>
            <td>{$entry['description']}</td>
            <td>" . number_format($entry['amount'], 2) . "</td>
            <td>" . number_format($entry['balance'], 2) . "</td>
        </tr>";
    }

    $content .= "</tbody></table></div></div></div>";

    return $content;
}


   protected function AllInvestment($date_from) {  
    $conn = $this->connect();

    if (empty($date_from)) {
        return '<b class="text-danger">Fill all data</b>';
    }

    $ReportName = "All Investor Investment Report :: Till $date_from";

    // ---- Fetch all investor net amounts directly from SQL ----
    $stmt = $conn->prepare("
        SELECT B.name, 
               SUM(
                   CASE 
                       WHEN A.type = 'add' THEN A.amount 
                       WHEN A.type = 'withdraw' THEN -A.amount 
                       ELSE 0 
                   END
               ) AS net_amount
        FROM investment_transactions A
        INNER JOIN investors B ON A.investor_id = B.id
        WHERE A.transaction_date <= :date_from
          AND A.type IN ('add','withdraw')
        GROUP BY B.id, B.name
        ORDER BY B.name ASC
    ");
    $stmt->execute([':date_from' => $date_from]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- Render HTML ----
    $content = "<div class='card card-primary'>
        <div class='card-header'><h3 class='card-title'>{$ReportName}</h3></div>
        <div class='card-body'><div class='table-responsive'>
        <table class='table table-bordered' id='example'>
            <thead>
                <tr>
                    <th>Investor Name</th>
                    <th>Current Investment</th>
                </tr>
            </thead>
            <tbody>";

    $grand_total = 0;
    foreach ($rows as $row) {
        $grand_total += $row['net_amount'];
        $content .= "<tr>
            <td>{$row['name']}</td>
            <td>" . number_format((float)$row['net_amount'], 2) . "</td>
        </tr>";
    }

    // ---- Footer Total ----
    $content .= "</tbody>
        <tfoot>
            <tr>
                <th>Total Inv.</th>
                <th>" . number_format($grand_total, 2) . "</th>
            </tr>
        </tfoot>
        </table></div></div></div>";

    return $content;
}





}
