<?php

class SectorDueManager extends Dbh
{

    public function updateDue($sector_id, $amount, $due_month)
    {
        $pdo = $this->connect();
        if (!$pdo) throw new Exception("Database connection failed.");

        try {
            $pdo->beginTransaction();

         
            // Daily due
            $stmt = $pdo->prepare("INSERT INTO sector_monthly_due ( sector_id, due_month, due)
                                   VALUES ( :sector_id, :due_month, :due)
                                   ON DUPLICATE KEY UPDATE due = due + VALUES(due)");
            $stmt->execute([
                ':sector_id' => $sector_id,
                ':due_month' => $due_month,
                ':due' => $amount
            ]);


            // Ledger due
            $stmt = $pdo->prepare("INSERT INTO sector_due_ledger ( sector_id, due)
                                   VALUES ( :sector_id, :due)
                                   ON DUPLICATE KEY UPDATE due = due + VALUES(due)");
            $stmt->execute([
                ':sector_id' => $sector_id,
                ':due' => $amount
            ]);

            $pdo->commit();




        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new Exception("Due update failed: " . $e->getMessage());
        }
    }

    public function rollbackDue( $sector_id, $amount, $due_month)
    {
        // rollback is just negative update
        $this->updateDue( $sector_id, -$amount, $due_month);
    }

    public function updateDueAfterRollback( $sector_id, $old_due, $new_due, $due_month)
    {
        // Step 1: rollback old
       $this->rollbackDue( $sector_id, $old_due, $due_month);

        // Step 2: apply new
        $this->updateDue( $sector_id, $new_due, $due_month);

        return true;
        
    }

    public function getDue( $sector_id)
    {
        $stmt = $this->connect()->prepare("SELECT due FROM sector_due_ledger WHERE  sector_id = ?");
        $stmt->execute([ $sector_id]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    public function setDue( $sector_id, $amount)
    {
        $stmt = $this->connect()->prepare("REPLACE INTO sector_due_ledger ( sector_id, due) VALUES (?, ?, ?)");
        $stmt->execute([ $sector_id, $amount]);
    }

   





public function getCurrentLedgerDue( $sector_id)
{
    $stmt = $this->connect()->prepare("SELECT due FROM sector_due_ledger 
        WHERE  sector_id = ?");
    $stmt->execute([ $sector_id]);
    return (float)($stmt->fetchColumn() ?: 0);
}



public function getLastDueDate( $sector_id)
{
    $stmt = $this->connect()->prepare("SELECT MAX(due) FROM sector_monthly_due WHERE  sector_id = ?");
    $stmt->execute([ $sector_id]);
    $timestamp = $stmt->fetchColumn();
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}


}
