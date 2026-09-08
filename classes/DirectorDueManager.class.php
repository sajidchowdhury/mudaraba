<?php

class DirectorDueManager extends Dbh
{

    public function updateDue($director_id, $amount, $due_month)
    {
        $pdo = $this->connect();
        if (!$pdo) throw new Exception("Database connection failed.");

        try {
            $pdo->beginTransaction();

         
            // Daily due
            $stmt = $pdo->prepare("INSERT INTO director_monthly_due ( director_id, due_month, due)
                                   VALUES ( :director_id, :due_month, :due)
                                   ON DUPLICATE KEY UPDATE due = due + VALUES(due)");
            $stmt->execute([
                ':director_id' => $director_id,
                ':due_month' => $due_month,
                ':due' => $amount
            ]);


            // Ledger due
            $stmt = $pdo->prepare("INSERT INTO director_due_ledger ( director_id, due)
                                   VALUES ( :director_id, :due)
                                   ON DUPLICATE KEY UPDATE due = due + VALUES(due)");
            $stmt->execute([
                ':director_id' => $director_id,
                ':due' => $amount
            ]);

            $pdo->commit();



        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new Exception("Due update failed: " . $e->getMessage());
        }
    }

    public function rollbackDue( $director_id, $amount, $due_month)
    {
        // rollback is just negative update
        $this->updateDue( $director_id, -$amount, $due_month);
    }

    public function updateDueAfterRollback( $director_id, $old_due, $new_due, $due_month)
    {
        // Step 1: rollback old
       $this->rollbackDue( $director_id, $old_due, $due_month);

        // Step 2: apply new
        $this->updateDue( $director_id, $new_due, $due_month);

        return true;
        
    }

    public function getDue( $director_id)
    {
        $stmt = $this->connect()->prepare("SELECT due FROM director_due_ledger WHERE  director_id = ?");
        $stmt->execute([ $director_id]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    public function setDue( $director_id, $amount)
    {
        $stmt = $this->connect()->prepare("REPLACE INTO director_due_ledger ( director_id, due) VALUES (?, ?)");
        $stmt->execute([ $director_id, $amount]);
    }

   

public function getCurrentLedgerDue( $director_id)
{
    $stmt = $this->connect()->prepare("SELECT due FROM director_due_ledger 
        WHERE  director_id = ?");
    $stmt->execute([ $director_id]);
    return (float)($stmt->fetchColumn() ?: 0);
}


public function getPreviousDueByDate($director_id, $date_from)
{
    // Convert input date to YYYY-MM
    $targetMonth = (new DateTime($date_from))->format('Y-m');

    $sql = "SELECT due 
            FROM director_monthly_due
            WHERE director_id = ?
              AND due_month < ?
            ORDER BY due_month DESC
            LIMIT 1";

    $stmt = $this->connect()->prepare($sql);
    $stmt->execute([$director_id, $targetMonth]);
    $previousDue = $stmt->fetchColumn();

    return $previousDue !== false ? (float) $previousDue : 0.00;
}



public function getLastDueDate( $director_id)
{
    $stmt = $this->connect()->prepare("SELECT MAX(due) FROM director_monthly_due WHERE  director_id = ?");
    $stmt->execute([ $director_id]);
    $timestamp = $stmt->fetchColumn();
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}


}
