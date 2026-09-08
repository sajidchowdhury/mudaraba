<?php

class InvestorProfit extends Dbh
{

    /**
     * Create or update investor monthly profit rows.
     * - $month must be 'YYYY-MM'
     * - On INSERT: due += advance_paid
     * - On UPDATE: due adjusted by (new_advance_paid - old_advance_paid) via updateDueAfterRollback
     *
     * @param array       $entries  Array of row data (per investor)
     * @param string      $month    'YYYY-MM'
     * @param float|null  $estimatedprofit  (unused in row-level save; kept for compatibility)
     * @param float|null  $actualprofit     (unused in row-level save; kept for compatibility)
     * @param float|null  $MyAmount         Stored in monthly_profit_summary.my_amount
     * @return array      Result payload
     * @throws Exception  On any failure (transaction rolled back)
     */
    protected function CreateOrUpdateData(
        array $entries,
        string $month,
        ?float $estimatedprofit,
        ?float $actualprofit,
        ?float $MyAmount
    ): array {
        $db = $this->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $now = date('Y-m-d H:i:s');
        $transaction_date = $month . '-' . date('d');


        // Ensure month is YYYY-MM
        $month = substr(trim($month), 0, 7);
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new Exception("Invalid month format. Expected YYYY-MM, got '{$month}'.");
        }

        // Use your actual Due manager class here

        $messages = [];

        try {
            $db->beginTransaction();

            // Prepared statements
            $checkStmt = $db->prepare("
                SELECT id
                FROM investor_monthly_profit_details
                WHERE month = ? AND investor_id = ?
                LIMIT 1
            ");

            $fetchOldStmt = $db->prepare("
                SELECT investment, investment_ratio, estimated_profit, actual_profit_before_deed, deed_ratio, final_profit, advance_paid
                FROM investor_monthly_profit_details
                WHERE month = ? AND investor_id = ?
                LIMIT 1
            ");

            $insertStmt = $db->prepare("
                INSERT INTO investor_monthly_profit_details
                (month, transaction_date,investor_id, investment, investment_ratio, estimated_profit, actual_profit_before_deed, deed_ratio, final_profit, advance_paid, created_at)
                VALUES (?, ? , ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $updateStmt = $db->prepare("
                UPDATE investor_monthly_profit_details
                SET investment = ?,
                    investment_ratio = ?,
                    estimated_profit = ?,
                    actual_profit_before_deed = ?,
                    deed_ratio = ?,
                    final_profit = ?,
                    advance_paid = ?
                WHERE month = ? AND investor_id = ?
            ");





            foreach ($entries as $idx => $entry) {
                // Normalize/cast inputs
                $investor_id               = isset($entry['investor_id']) ? (int) $entry['investor_id'] : 0;
                $investment                = isset($entry['investment']) ? (float) $entry['investment'] : 0.0;
                $investment_ratio          = isset($entry['investment_ratio']) ? (float) $entry['investment_ratio'] : 0.0;
                $estimated_profit          = isset($entry['estimated_profit']) ? (float) $entry['estimated_profit'] : 0.0;
                $actual_profit_before_deed = isset($entry['actual_profit_before_deed']) ? (float) $entry['actual_profit_before_deed'] : 0.0;
                // handle possible typo deed_ration
                $deed_ratio                = isset($entry['deed_ratio'])
                                                ? (float) $entry['deed_ratio']
                                                : (isset($entry['deed_ration']) ? (float) $entry['deed_ration'] : 0.0);
                $final_profit              = isset($entry['actual_profit']) ? (float) $entry['actual_profit'] : 0.0;
                $advance_paid              = isset($entry['advance_paid']) ? (float) $entry['advance_paid'] : 0.0;

                // Basic validations
                if ($investor_id <= 0) {
                    throw new Exception("Row #".($idx+1).": Missing or invalid investor_id.");
                }
                if ($investment < 0)                throw new Exception("Investor {$investor_id}: investment < 0.");
                if ($investment_ratio < 0)          throw new Exception("Investor {$investor_id}: investment_ratio < 0.");
                if ($deed_ratio < 0 || $deed_ratio > 100)
                                                    throw new Exception("Investor {$investor_id}: deed_ratio must be between 0 and 100.");
              //  if ($estimated_profit < 0 || $final_profit < 0 || $advance_paid < 0 || $actual_profit_before_deed < 0) {
                //                                    throw new Exception("Investor {$investor_id}: profit/advance values must be non-negative.");
               // }

                // Existence check
                $checkStmt->execute([$month, $investor_id]);
                $existingId = $checkStmt->fetchColumn();

                if ($existingId) {
                    // Fetch old values before update (for due delta on advance_paid)
                    $fetchOldStmt->execute([$month, $investor_id]);
                    $old = $fetchOldStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$old) {
                        throw new Exception("Failed to fetch existing row for investor {$investor_id}, month {$month}.");
                    }

                    $old_advance = (float) $old['advance_paid'];
                    $new_advance = $advance_paid;

                    // Update row
                    $ok = $updateStmt->execute([
                        $investment,
                        $investment_ratio,
                        $estimated_profit,
                        $actual_profit_before_deed,
                        $deed_ratio,
                        $final_profit,
                        $advance_paid,
                        $month,
                        $investor_id
                    ]);
                    if ($ok === false) {
                        $err = $updateStmt->errorInfo();
                        throw new Exception("Failed to UPDATE investor {$investor_id}: " . ($err[2] ?? 'unknown error'));
                    }


                    $messages[] = "Updated investor_id {$investor_id} ({$month}); due adjusted by advance delta.";
                } else {
                    // Insert new row
                    $ok = $insertStmt->execute([
                        $month,
                        $transaction_date,
                        $investor_id,
                        $investment,
                        $investment_ratio,
                        $estimated_profit,
                        $actual_profit_before_deed,
                        $deed_ratio,
                        $final_profit,
                        $advance_paid,
                        $now
                    ]);
                    if ($ok === false) {
                        $err = $insertStmt->errorInfo();
                        throw new Exception("Failed to INSERT investor {$investor_id}: " . ($err[2] ?? 'unknown error'));
                    }

                    // On insert, due += advance_paid

                   // if ($advance_paid > 0) {
                       
                  //  }else{
                        "Inserted investor_id {$advance_paid} ({$month}); due increased by advance.";
                   // }

                    $messages[] = "Inserted investor_id {$investor_id} ({$month}); due increased by advance.";
                }
            } // foreach entries

            // Update monthly summary (my_amount included)
            $this->updateMonthlyProfitSummary($month, (float)($MyAmount ?? 0));
            $messages[] = "Updated monthly profit summary for {$month}.";

            $db->commit();

            return [
                'status'  => 'success',
                'message' => "Saved profit distribution for {$month}.",
                'details' => $messages
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                try { $db->rollBack(); } catch (\Throwable $t) {}
            }
            throw new Exception("CreateOrUpdateData failed: " . $e->getMessage());
        }
    }

    /**
     * Upsert monthly totals into monthly_profit_summary (strict; throws on failure).
     * - Includes my_amount
     */
    private function updateMonthlyProfitSummary(string $month, float $MyAmount): void
    {
        // Compute totals from details

                $db = $this->connect();



        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(estimated_profit), 0)           AS total_estimated_profit,
                COALESCE(SUM(actual_profit_before_deed), 0)  AS total_actual_profit_before_deed,
                COALESCE(SUM(final_profit), 0)               AS total_final_profit,
                COALESCE(SUM(advance_paid), 0)               AS total_advance_paid
            FROM investor_monthly_profit_details
            WHERE month = ?
        ");
        if (!$stmt->execute([$month])) {
            $err = $stmt->errorInfo();
            throw new Exception("Failed to compute monthly totals: " . ($err[2] ?? 'unknown'));
        }
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$summary) {
            throw new Exception("Failed to fetch computed summary for month {$month}.");
        }

        $total_est     = (float) ($summary['total_estimated_profit'] ?? 0);
        $total_actual  = (float) ($summary['total_actual_profit_before_deed'] ?? 0);
        $total_final   = (float) ($summary['total_final_profit'] ?? 0);
        $total_advance = (float) ($summary['total_advance_paid'] ?? 0);
        $transaction_date = $month . '-' . date('d');

        // Upsert summary
        $up = $db->prepare("
            INSERT INTO monthly_profit_summary
            (month, transaction_date, total_estimated_profit, total_actual_profit_before_deed, total_final_profit, total_advance_paid, my_amount)
            VALUES (?, ? ,?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_estimated_profit = VALUES(total_estimated_profit),
                total_actual_profit_before_deed = VALUES(total_actual_profit_before_deed),
                total_final_profit = VALUES(total_final_profit),
                total_advance_paid = VALUES(total_advance_paid),
                my_amount = VALUES(my_amount)
        ");
        $ok = $up->execute([
            $month,
            $transaction_date,
            $total_est,
            $total_actual,
            $total_final,
            $total_advance,
            $MyAmount
        ]);
        if ($ok === false) {
            $err = $up->errorInfo();
            throw new Exception("Failed to upsert monthly_profit_summary: " . ($err[2] ?? 'unknown'));
        }
    }

    /**
     * Helper: fetch details by month (unchanged)
     */
    public function MonthlyProfitDetails(string $month)
    {
        $stmt = $this->connect()->prepare("
            SELECT impd.*, i.name AS investor_name
            FROM investor_monthly_profit_details impd
            JOIN investors i ON i.id = impd.investor_id
            WHERE impd.month = :month
        ");
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


  public function AutoSave($month, bool $debug = false) {



        $debugData = [
            'month' => $month,
            'timestamp' => date('Y-m-d H:i:s'),
            'investors' => [],
            'total_investment' => 0,
            'sector_profit' => [],
            'MyAmount' => 0,
            'status' => 'pending',
            'messages' => []
        ];

        try {
            error_log("=== AutoSave called for month: {$month} ===");

            // Use same $pdo for collaborators if they support it
            $newInvestor  = new NewInvestor();
            $investments  = new Investments();
            $sectorProfit = new SectorProfit();

            // --- Fetch all investors ---
            $rows = $newInvestor->ListDataByDateRange($month);
            $debugData['messages'][] = "Fetched investors count: " . count($rows);
            error_log("Fetched investors: " . count($rows));

            if (empty($rows)) {
                $debugData['status'] = 'no_investors';
                $debugData['messages'][] = "No investors found. Exiting AutoSave.";
                //if ($debug) $this->writeDebugFile($month, $debugData);
                return;
            }

            // --- Total investment ---
            $totalInvestment = (float)$investments->TotalInvestment();
            $debugData['total_investment'] = $totalInvestment;
            $debugData['messages'][] = "Total investment across all investors: {$totalInvestment}";
            error_log("Total investment: {$totalInvestment}");

            if ($totalInvestment <= 0) {
                $debugData['status'] = 'zero_total_investment';
                $debugData['messages'][] = "Total investment is zero. Exiting AutoSave.";
               // if ($debug) $this->writeDebugFile($month, $debugData);
                return;
            }

            // --- Sector profit ---
            $TotalProfit = $sectorProfit->MonthlyProfit($month);
            $debugData['sector_profit'] = $TotalProfit;

            $estimated = $TotalProfit['estimatedprofit'] ?? 0;
            $actual    = $TotalProfit['actualprofit'] ?? 0;
            $debugData['messages'][] = "Estimated profit: {$estimated}, Actual profit: {$actual}";

            // --- Loop through investors ---
            $investorList = [];
            foreach ($rows as $row) {

                $invId     = (int)$row['id'];
                $investAmt = (float)$investments->TotalInvestmentByInvestor($invId);
                $ratio     = $totalInvestment > 0 ? ($investAmt / $totalInvestment) : 0;

                $estimated_disbursement = $estimated * $ratio;
                $actual_share           = $actual > 0 ? $actual * $ratio : 0;
                $deed_ratio             = isset($row['profit']) ? (float)$row['profit'] : 0;

                $profit_actual = round($actual_share * $deed_ratio / 100);
                $advance_paid  = round($estimated_disbursement - $profit_actual);

                $investorList[] = [
                    'investor_id'               => $invId,
                    'investment'                => $investAmt,
                    'investment_ratio'          => $ratio,
                    'estimated_profit'          => $estimated_disbursement,
                    'actual_profit_before_deed' => $actual_share,
                    'deed_ratio'                => $deed_ratio,
                    'actual_profit'             => $profit_actual,
                    'advance_paid'              => $advance_paid
                ];

                $debugData['investors'][] = end($investorList);
            }

            // --- Total distributed profit ---
            $SumofProfitasperdeed = array_sum(array_column($investorList, 'actual_profit'));

            $MyAmount = $actual - $SumofProfitasperdeed;

            // --- Save to DB (using shared PDO) ---
            $result = $this->CreateOrUpdateData($investorList, $month, $estimated, $actual, $MyAmount);
            $debugData['messages'][] = "DB save result: " . json_encode($result);

            $debugData['status'] = 'success';
            $debugData['messages'][] = "AutoSave completed successfully.";

        } catch (\Throwable $e) {
            $debugData['status'] = 'error';
            $debugData['messages'][] = "AutoSave failed: " . $e->getMessage();
            error_log("AutoSave exception: " . $e->getMessage());
            throw $e;
        } finally {
            if ($debug) {
               // $this->writeDebugFile($month, $debugData);
            }
        }
    }



/**
 * Writes debug JSON file for AutoSave
 */
private function writeDebugFile(string $month, array $data): void {
    $folder = __DIR__ . '/autosave_debug';
    if (!is_dir($folder)) mkdir($folder, 0775, true);
    $filename = $folder . '/autosave_' . str_replace('-', '_', $month) . '_' . time() . '.json';
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    error_log("AutoSave debug file written: {$filename}");
}


public function InvestorWiseLedger($investor_id) {

    $sql = "SELECT due  FROM investor_profit_due_ledger WHERE investor_id = :investor_id";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(':investor_id', $investor_id, PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}






}
