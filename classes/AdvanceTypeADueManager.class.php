<?php

class AdvanceTypeADueManager extends Dbh
{
    /**
     * Update or insert daily Adv Profit Adjusting Fund.
     * Adds (or subtracts) amount to the existing record for that date.
     */
    public function updateFund($amount, $date = null)
    {
        $pdo = $this->connect();
        if (!$pdo) throw new Exception("Database connection failed.");

        try {
            $pdo->beginTransaction();

            $date = $date ?: date('Y-m-d');

            $stmt = $pdo->prepare("
                INSERT INTO adv_profit_adjusting_fund_type_A (`date`, `amount`)
                VALUES (:date, :amount)
                ON DUPLICATE KEY UPDATE 
                    amount = amount + VALUES(amount)
            ");
            $stmt->execute([
                ':date'   => $date,
                ':amount' => $amount
            ]);

            $pdo->commit();
            return ["status" => "success", "message" => "Fund updated successfully."];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ["status" => "error", "message" => "Fund update failed: " . $e->getMessage()];
        }
    }

    /**
     * Rollback / reverse a previous fund update (subtract the same amount).
     */
    public function rollbackFund($amount, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        return $this->updateFund(-$amount, $date);
    }

    /**
     * Replace the fund value for a specific date (use carefully).
     */
    public function setFund($amount, $date = null)
    {
        $pdo = $this->connect();
        $date = $date ?: date('Y-m-d');

        $stmt = $pdo->prepare("
            INSERT INTO adv_profit_adjusting_fund_type_A (`date`, `amount`)
            VALUES (:date, :amount)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount)
        ");
        $stmt->execute([':date' => $date, ':amount' => $amount]);
        return true;
    }

    /**
     * Get the fund value for a specific date.
     */
    public function getFundByDate($date = null)
    {
        $pdo = $this->connect();
        $date = $date ?: date('Y-m-d');

        $stmt = $pdo->prepare("SELECT amount FROM adv_profit_adjusting_fund_type_A WHERE `date` = ?");
        $stmt->execute([$date]);
        return (float)($stmt->fetchColumn() ?: 0.00);
    }

    /**
     * Get the latest (most recent) fund record.
     */
    public function getLatestFund()
    {
        $pdo = $this->connect();
        $stmt = $pdo->query("SELECT amount FROM adv_profit_adjusting_fund_type_A ORDER BY `date` DESC LIMIT 1");
        return (float)($stmt->fetchColumn() ?: 0.00);
    }

    /**
     * Get the total accumulated fund over time.
     */
    public function getTotalFund()
    {
        $pdo = $this->connect();
        $stmt = $pdo->query("SELECT SUM(amount) FROM adv_profit_adjusting_fund_type_A");
        return (float)($stmt->fetchColumn() ?: 0.00);
    }
}
