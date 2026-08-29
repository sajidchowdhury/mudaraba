<?php 

class SectorProfit extends Dbh {
    use SharedFunctionalityTrait;

    protected function CreateData($profit_month, $items) {
    date_default_timezone_set('Asia/Dhaka');

    try {
        $pdo = $this->connect();
        if (!$pdo) throw new Exception("Database connection failed.");

        $pdo->beginTransaction();
        $updated_at = date('Y-m-d');
        $spdu = new SectorProfitDueManager();

        $total_estimated = 0;
        $total_actual    = 0;
        $transaction_date = $profit_month . '-' . date("d");

        // --- Step 1: Update/Insert monthly_sector_profit ---
        foreach ($items as $item) {
            $sector_id        = $item['sector_id'];
            $estimated_profit = (float)$item['est_amount'];
            $actual_profit    = (float)$item['amount'];

            $total_estimated += $estimated_profit;
            $total_actual    += $actual_profit;

            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) as count, id,estimated_profit,actual_profit
                FROM monthly_sector_profit 
                WHERE month = :profit_month AND sector_id = :sector_id
            ");
            $checkStmt->execute([
                ':profit_month' => $profit_month,
                ':sector_id'    => $sector_id
            ]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $stmt = $pdo->prepare("
                    UPDATE monthly_sector_profit 
                    SET estimated_profit = :estimated_profit, 
                        actual_profit = :actual_profit, 
                        updated_at = :updated_at 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':estimated_profit' => $estimated_profit,
                    ':actual_profit'    => $actual_profit,
                    ':updated_at'       => $updated_at,
                    ':id'               => $result['id']
                ]);

            $old_amount = $result['estimated_profit'] - $result['actual_profit'];
            $new_amount = $estimated_profit - $actual_profit;
            $spdu->updateDueAfterRollback( $sector_id, $old_amount, $new_amount, $profit_month);


            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO monthly_sector_profit 
                        (sector_id, month, estimated_profit, actual_profit, created_at, transaction_date) 
                    VALUES 
                        (:sector_id, :profit_month, :estimated_profit, :actual_profit, :created_at, :transaction_date)
                ");
                $stmt->execute([
                    ':sector_id'        => $sector_id,
                    ':profit_month'     => $profit_month,
                    ':estimated_profit' => $estimated_profit,
                    ':actual_profit'    => $actual_profit,
                    ':created_at'       => $updated_at,
                    ':transaction_date' => $transaction_date
                ]);

                $amount = $estimated_profit - $actual_profit;
                $spdu->updateDue($sector_id, $amount, $profit_month);

            }
        }

        // --- Step 2: Get investors ---
        $newInvestor = new NewInvestor();
        $rows        = $newInvestor->ListDataByDateRange($profit_month);

        $investments     = new Investments();
        $totalInvestment = 0;

        $due = new InvestorProfitDueManager();



        foreach ($rows as $row) {
            $totalInvestment += (float)$investments->InvestmentTillMonth((int)$row['id'],$profit_month);
        }

        // --- Step 3: Loop through investors ---
        $sum_of_profit_actual = 0;
        $sum_of_final_profit  = 0;
        $sum_of_advance_paid  = 0;

        foreach ($rows as $row) {
            $invId     = (int)$row['id'];
            $investAmt = (float)$investments->InvestmentTillMonth($invId,$profit_month);
            $ratio     = $totalInvestment > 0 ? ($investAmt / $totalInvestment) : 0;

            $estimated_disbursement = $total_estimated * $ratio;
            $actual_share           = $total_actual > 0 ? $total_actual * $ratio : 0;
            $deed_ratio             = isset($row['profit']) ? (float)$row['profit'] : 0;

            $profit_actual = round($actual_share * $deed_ratio / 100);
            $advance_paid  = round($estimated_disbursement - $profit_actual);

            $sum_of_profit_actual += $actual_share;   // total before deed
            $sum_of_final_profit  += $profit_actual;  // final distributed
            $sum_of_advance_paid  += $advance_paid;

            // --- Step 4: Insert or Update investor_monthly_profit_details ---
            $checkStmt = $pdo->prepare("
                SELECT id,advance_paid FROM investor_monthly_profit_details 
                WHERE month = :month AND investor_id = :invId
            ");
            $checkStmt->execute([
                ':month' => $profit_month,
                ':invId' => $invId
            ]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                $stmt = $pdo->prepare("
                    UPDATE investor_monthly_profit_details
                    SET 
                        investment = :investment,
                        investment_ratio = :ratio,
                        estimated_profit = :estimated_profit,
                        actual_profit_before_deed = :actual_profit_before_deed,
                        deed_ratio = :deed_ratio,
                        final_profit = :final_profit,
                        advance_paid = :advance_paid
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':investment'               => $investAmt,
                    ':ratio'                    => $ratio,
                    ':estimated_profit'         => $estimated_disbursement,
                    ':actual_profit_before_deed'=> $actual_share,
                    ':deed_ratio'               => $deed_ratio,
                    ':final_profit'             => $profit_actual,
                    ':advance_paid'             => $advance_paid,
                    ':id'                       => $exists['id']
                ]);


                $due->updateDueAfterRollback( $invId, $exists['advance_paid'], $advance_paid, $profit_month);


            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO investor_monthly_profit_details 
                        (month, transaction_date, investor_id, investment, investment_ratio, 
                         estimated_profit, actual_profit_before_deed, deed_ratio, final_profit, advance_paid)
                    VALUES 
                        (:month, :transaction_date, :investor_id, :investment, :ratio, 
                         :estimated_profit, :actual_profit_before_deed, :deed_ratio, :final_profit, :advance_paid)
                ");
                $stmt->execute([
                    ':month'                   => $profit_month,
                    ':transaction_date'        => $transaction_date,
                    ':investor_id'             => $invId,
                    ':investment'              => $investAmt,
                    ':ratio'                   => $ratio,
                    ':estimated_profit'        => $estimated_disbursement,
                    ':actual_profit_before_deed'=> $actual_share,
                    ':deed_ratio'              => $deed_ratio,
                    ':final_profit'            => $profit_actual,
                    ':advance_paid'            => $advance_paid
                ]);


                $due->updateDue($invId, $advance_paid, $profit_month);

            }
        }


       $Mydue = new DirectorDueManager();


        $MyAmount = $total_actual - $sum_of_final_profit;

        // --- Step 5: Insert/Update monthly_profit_summary ---
        $checkStmt = $pdo->prepare("SELECT month,my_amount FROM monthly_profit_summary WHERE month = :month");
        $checkStmt->execute([':month' => $profit_month]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {


            $stmt = $pdo->prepare("
                UPDATE monthly_profit_summary
                SET 
                    total_estimated_profit = :total_estimated,
                    total_actual_profit_before_deed = :total_actual_before_deed,
                    total_final_profit = :total_final,
                    total_advance_paid = :total_advance,
                    my_amount = :my_amount,
                    updated_at = NOW()
                WHERE month = :month
            ");
            $stmt->execute([
                ':total_estimated'         => $total_estimated,
                ':total_actual_before_deed'=> $sum_of_profit_actual,
                ':total_final'             => $sum_of_final_profit,
                ':total_advance'           => $sum_of_advance_paid,
                ':my_amount'               => $MyAmount,
                ':month'                   => $profit_month
            ]);

          $Mydue->updateDueAfterRollback( 1, $exists['my_amount'], $MyAmount, $profit_month);

        } else {


            $stmt = $pdo->prepare("
                INSERT INTO monthly_profit_summary
                    (month, transaction_date, total_estimated_profit, total_actual_profit_before_deed,
                     total_final_profit, total_advance_paid, my_amount)
                VALUES
                    (:month, :transaction_date, :total_estimated, :total_actual_before_deed,
                     :total_final, :total_advance, :my_amount)
            ");
            $stmt->execute([
                ':month'                   => $profit_month,
                ':transaction_date'        => $transaction_date,
                ':total_estimated'         => $total_estimated,
                ':total_actual_before_deed'=> $sum_of_profit_actual,
                ':total_final'             => $sum_of_final_profit,
                ':total_advance'           => $sum_of_advance_paid,
                ':my_amount'               => $MyAmount
            ]);


         $Mydue->updateDue(1, $MyAmount, $profit_month);


        }

    
        $pdo->commit();
        return ["status" => "success", "message" => "Processed successfully", "remaining" => $MyAmount];

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["status" => "error", "message" => "Create failed: " . $e->getMessage()];
    }
}



public function MonthlyProfit($month) {
    try {
        $sql = '
            SELECT 
                COALESCE(SUM(estimated_profit), 0) AS estimatedprofit,
                COALESCE(SUM(actual_profit), 0) AS actualprofit

            FROM monthly_sector_profit 
            WHERE month = :month
        ';
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindValue(':month', $month, PDO::PARAM_STR); // Use PARAM_STR to support 'YYYY-MM' format
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'estimatedprofit' => 0,
            'actualprofit' => 0
        ];
        
    } catch (PDOException $e) {
        // Log error if needed
        return [
            'estimatedprofit' => 0,
            'actualprofit' => 0,
            'error' => $e->getMessage()
        ];
    }
}

public function MonthlySectorReceivablePayable($month) {
    $stmt = $this->connect()->prepare('
        SELECT 
            A.sector_id,
            B.name as SectorName,
            A.estimated_profit,
            A.actual_profit,
            (A.actual_profit - (A.estimated_profit)) AS difference
        FROM monthly_sector_profit A
        JOIN sectors B ON (A.sector_id = B.id )
        WHERE A.month = :month
            AND (A.actual_profit - (A.estimated_profit )) != 0
    ');
    $stmt->bindParam(':month', $month, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $receivable = [];
    $payable = [];

    foreach ($results as $row) {
        $entry = [
            'sector_id'        => $row['sector_id'],
            'sector_name'        => $row['SectorName'],
            'estimated_profit' => (float) $row['estimated_profit'],
            'actual_profit'    => (float) $row['actual_profit'],
            'difference'       => (float) $row['difference'],
        ];

        if ($row['difference'] > 0) {
            $payable[] = $entry;
        } elseif ($row['difference'] < 0) {
            $receivable[] = $entry;
        }
    }

    return [
        'receivable_from_investors' => $receivable,
        'payable_to_investors'      => $payable
    ];
}



   public function MonthlyProfitDetails($month) {
    // Expect month in "YYYY-MM"
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        return []; // or throw new InvalidArgumentException
    }

    $response = [];

    $stmt = $this->connect()->prepare(
        'SELECT sector_id, estimated_profit, actual_profit
         FROM monthly_sector_profit 
         WHERE month = :month'
    );
    // month is string like "2025-08"
    $stmt->bindParam(':month', $month, PDO::PARAM_STR);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response[$row['sector_id']] = [
            'estimated_profit' => $row['estimated_profit'],
            'actual_profit'  => $row['actual_profit']

        ];
    }

    return $response;
}



public function SectorProfitDue($sector_id) {

    $sql = "SELECT due AS total FROM sector_profit_due_ledger WHERE sector_id = :sector_id";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}


public function TotalSectorProfitDue() {
    $sql = "SELECT SUM(due) AS total FROM sector_profit_due_ledger";
    $stmt = $this->connect()->prepare($sql);
    $stmt->execute();
    $total = $stmt->fetchColumn();
    return $total !== false ? (float) $total : 0.00;
}


public function UpdateSectorAdvanceProfit($amount, $sector_id, $transaction_date, $month) {
    try {
        $stmt = $this->connect()->prepare("
            INSERT INTO `advance_profit_adjustment` 
                (`sector_id`, `amount`, `month`, `transaction_date`)
            VALUES 
                (:sector_id, :amount, :month, :transaction_date)
        ");

        $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':transaction_date', $transaction_date);

        // ✅ this was missing
        $stmt->execute();

        // Update due after successful insert
        $profit_due = new SectorProfitDueManager();
        $profit_due->updateDue($sector_id, -$amount, $month);

    } catch (PDOException $e) {
        // optional error log
        error_log("UpdateSectorAdvanceProfit failed: " . $e->getMessage());
        throw $e; // or return false
    }
}



}

