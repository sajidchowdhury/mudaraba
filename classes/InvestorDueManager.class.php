<?php

class InvestorDueManager extends Dbh
{

    public function updateDue($investor_id, $amount, $due_month)
    {
        $pdo = $this->connect();
        if (!$pdo) throw new Exception("Database connection failed.");

        try {
            $pdo->beginTransaction();

         
            // Daily due
            $stmt = $pdo->prepare("INSERT INTO investor_monthly_due ( investor_id, due_month, due)
                                   VALUES ( :investor_id, :due_month, :due)
                                   ON DUPLICATE KEY UPDATE due = due + VALUES(due)");
            $stmt->execute([
                ':investor_id' => $investor_id,
                ':due_month' => $due_month,
                ':due' => $amount
            ]);


            // Ledger due
            $stmt = $pdo->prepare("INSERT INTO investor_due_ledger ( investor_id, due)
                                   VALUES ( :investor_id, :due)
                                   ON DUPLICATE KEY UPDATE due = due + VALUES(due)");
            $stmt->execute([
                ':investor_id' => $investor_id,
                ':due' => $amount
            ]);

            $pdo->commit();



        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new Exception("Due update failed: " . $e->getMessage());
        }
    }

    public function rollbackDue( $investor_id, $amount, $due_month)
    {
        // rollback is just negative update
        $this->updateDue( $investor_id, -$amount, $due_month);
    }

    public function updateDueAfterRollback( $investor_id, $old_due, $new_due, $due_month)
    {
        // Step 1: rollback old
       $this->rollbackDue( $investor_id, $old_due, $due_month);

        // Step 2: apply new
        $this->updateDue( $investor_id, $new_due, $due_month);

        return true;
        
    }

    public function getDue( $investor_id)
    {
        $stmt = $this->connect()->prepare("SELECT due FROM investor_due_ledger WHERE  investor_id = ?");
        $stmt->execute([ $investor_id]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    public function setDue( $investor_id, $amount)
    {
        $stmt = $this->connect()->prepare("REPLACE INTO investor_due_ledger ( investor_id, due) VALUES (?, ?, ?)");
        $stmt->execute([ $investor_id, $amount]);
    }

   

public function getCurrentLedgerDue( $investor_id)
{
    $stmt = $this->connect()->prepare("SELECT due FROM investor_due_ledger 
        WHERE  investor_id = ?");
    $stmt->execute([ $investor_id]);
    return (float)($stmt->fetchColumn() ?: 0);
}



public function getLastDueDate( $investor_id)
{
    $stmt = $this->connect()->prepare("SELECT MAX(due) FROM investor_monthly_due WHERE  investor_id = ?");
    $stmt->execute([ $investor_id]);
    $timestamp = $stmt->fetchColumn();
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}


}
