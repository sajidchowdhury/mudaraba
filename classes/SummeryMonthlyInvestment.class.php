<?php

class SummeryMonthlyInvestment extends Dbh
{

    public function updateDue($month, $investor_id, $amount)
    {
        $pdo = $this->connect();
        if (!$pdo) throw new Exception("Database connection failed.");

        try {
            $pdo->beginTransaction();

          
            $stmt = $pdo->prepare("INSERT INTO summery_monthly_investment (month, investor_id, amount)
                                   VALUES (:month, :investor_id, :amount)
                                   ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)");
            $stmt->execute([
                ':month' => $month,
                ':investor_id' => $investor_id,
                ':amount' => $amount
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new Exception("Due update failed: " . $e->getMessage());
        }
    }

    public function rollbackDue($month, $investor_id, $amount)
    {
        // rollback is just negative update
        $this->updateDue($month, $investor_id, -$amount);
    }

    public function updateDueAfterRollback($month, $investor_id, $old_amount, $new_amount)
    {
        // Step 1: rollback old
       $this->rollbackDue($month, $investor_id, $old_amount);

        // Step 2: apply new
        $this->updateDue($month, $investor_id, $new_amount);

        return true;
        
    }

    public function getInvestment($investor_id, $month)
    {
        $stmt = $this->connect()->prepare("SELECT amount FROM summery_monthly_investment WHERE investor_id = ? AND month = ?");
        $stmt->execute([$investor_id, $month]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    public function setDue($investor_id, $month,$amount)
    {
        $stmt = $this->connect()->prepare("REPLACE INTO summery_monthly_investment (investor_id, month, amount) VALUES (?, ?, ?)");
        $stmt->execute([$investor_id, $month, $amount]);
    }

    public function rollbackTransactionByRef($transactionId)
{
    try {
        $pdo = $this->connect();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM account_transaction WHERE id = :transactionId");
        $stmt->execute([':transactionId' => $transactionId]);
        $tr = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tr) {
            throw new Exception("Transaction not found.");
        }

        // Validate required fields
        if (empty($tr['entity_type']) || empty($tr['account_id']) || empty($tr['transaction_date']) || empty($tr['time'])) {
            throw new Exception("Transaction data incomplete.");
        }

        $entry_type = $tr['entity_type'];
        $entity_id = $tr['account_id'];

        // ✅ FIXED HERE
        $amount = ($tr['transaction_type'] === 'debit' ? $tr['amount'] : -$tr['amount']);

        $date = $tr['transaction_date'];
        $time = $tr['time'];

        $this->rollbackDue($entry_type, $entity_id, $amount, $date, $time);

        // Delete the transaction
        $deleteStmt = $pdo->prepare("DELETE FROM account_transaction WHERE id = :transactionId");
        $deleteStmt->execute([':transactionId' => $transactionId]);

        $pdo->commit();
        return ['status' => 'success', 'message' => 'Transaction rollback complete.'];
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['status' => 'error', 'message' => 'Rollback failed: ' . $e->getMessage()];
    }
}

public function getInvoiceDue($entity_type, $entity_id, $invoice_time)
{
    $stmt = $this->connect()->prepare("SELECT due FROM due_timewise WHERE entity_type = ? AND entity_id = ? AND due_time = ?");
    $stmt->execute([$entity_type, $entity_id, $invoice_time]);
    return (float)($stmt->fetchColumn() ?: 0);
}


public function getPreviousDue($entity_type, $entity_id, $invoice_time)
{
    $stmt = $this->connect()->prepare("SELECT SUM(due) FROM due_timewise 
        WHERE entity_type = ? AND entity_id = ? AND due_time < ?");
    $stmt->execute([$entity_type, $entity_id, $invoice_time]);
    return (float)($stmt->fetchColumn() ?: 0);
}


public function getCurrentLedgerDue($entity_type, $entity_id)
{
    $stmt = $this->connect()->prepare("SELECT due FROM due_ledger 
        WHERE entity_type = ? AND entity_id = ?");
    $stmt->execute([$entity_type, $entity_id]);
    return (float)($stmt->fetchColumn() ?: 0);
}


public function getDueBreakdownForInvoice($entity_type, $entity_id, $invoice_time)
{
    $previous_due = $this->getPreviousDue($entity_type, $entity_id, $invoice_time);
    $this_invoice_due = $this->getInvoiceDue($entity_type, $entity_id, $invoice_time);
    $total_due = $previous_due + $this_invoice_due;

    return [
        'previous_due' => $previous_due,
        'this_invoice_due' => $this_invoice_due,
        'total_due_after_invoice' => $total_due,
        'ledger_due_now' => $this->getCurrentLedgerDue($entity_type, $entity_id)
    ];
}

public function getLastDueDate($entity_type, $entity_id)
{
    $stmt = $this->connect()->prepare("SELECT MAX(due_time) FROM due_timewise WHERE entity_type = ? AND entity_id = ?");
    $stmt->execute([$entity_type, $entity_id]);
    $timestamp = $stmt->fetchColumn();
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}


}
