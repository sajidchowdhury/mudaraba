<?php 

class AdvanceProfitAdjustmentTypeA extends Dbh {

    

    protected function createAdj($investors, $sectors, $adv_adjust) {
        try {
            $db = $this->connect();
            $db->beginTransaction();

            if (session_status() === PHP_SESSION_NONE) session_start();
            $employee_id = $_SESSION['employee_id'] ?? 'unknown';
            date_default_timezone_set('Asia/Dhaka');
            $created_at = date('Y-m-d H:i:s');
            $transaction_date = date('Y-m-d');
            $month = date('Y-m');


            $Invdue = new InvestorProfitDueManager();
            $Sectdue = new SectorProfitDueManager();
            $Adj = new AdvanceTypeADueManager();



            // --- 1️⃣ Save Investor side adjustments ---
            $inv_sql = "INSERT INTO investor_advance_profit_adjustment 
                        (investor_id, amount, transaction_date, month)
                        VALUES (:investor_id, :amount, :transaction_date, :month)";
            $inv_stmt = $db->prepare($inv_sql);

            foreach ($investors as $inv) {
                $investor_id = $inv['investor_id'];
                $amount = floatval($inv['amount']) ?? 0;

                if ($amount <= 0) continue; // skip zero or invalid rows

                $inv_stmt->bindParam(':investor_id', $investor_id);
                $inv_stmt->bindParam(':amount', $amount);
                $inv_stmt->bindParam(':transaction_date', $transaction_date);
                $inv_stmt->bindParam(':month', $month);

                if (!$inv_stmt->execute()) {
                    throw new Exception("Failed to save investor adjustment (Investor ID: $investor_id)");
                }

                $Invdue->updateDue($investor_id, -$amount, $transaction_date);

               $Adj->updateFund($amount, $transaction_date);

            }

            // --- 2️⃣ Save Sector side adjustments ---
            $sec_sql = "INSERT INTO advance_profit_adjustment 
                        (sector_id, amount, transaction_date, month)
                        VALUES (:sector_id, :amount, :transaction_date, :month)";
            $sec_stmt = $db->prepare($sec_sql);

            foreach ($sectors as $sec) {
                $sector_id = $sec['sector_id'];
                $amount = floatval($sec['amount']) ?? 0;

                if ($amount <= 0) continue;

                $sec_stmt->bindParam(':sector_id', $sector_id);
                $sec_stmt->bindParam(':amount', $amount);
                $sec_stmt->bindParam(':transaction_date', $transaction_date);
                $sec_stmt->bindParam(':month', $month);


                $Sectdue->updateDue($sector_id, -$amount, $month);
                $Adj->updateFund(-$amount, $transaction_date);


                if (!$sec_stmt->execute()) {
                    throw new Exception("Failed to save sector adjustment (Sector ID: $sector_id)");
                }
            }

            // --- 3️⃣ Verify total consistency ---
        $inv_total = array_sum(array_column($investors, 'amount'));
$sec_total = array_sum(array_column($sectors, 'amount'));

//if (round($inv_total, 2) !== round($sec_total, 2)) {
//    throw new Exception("Investor and sector totals do not match.");
//}



            // --- 4️⃣ Commit the transaction ---
            $db->commit();
            return ["status" => "success", "message" => "Advance profit adjustment saved successfully."];

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ["status" => "error", "message" => $e->getMessage()];
        } finally {
            $inv_stmt = null;
            $sec_stmt = null;
        }
    }




}
