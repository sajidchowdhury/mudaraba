<?php

class OpeningSectorAdvancePlate {
    private $id;

    public function __construct($id = 'New') {
        $this->id = $id;
    }

    public function SetupForm() {
        // CSRF token
        $form_token = md5(uniqid(rand(), true));
        $_SESSION['form_tokens'][$form_token] = time();
        $csrf_token = $_SESSION['csrf_token'] ?? '';

        $List2 = new NewSector();

        // Default values
        $action_id        = '';
        $amount           = 0.00;
        $remarks          = 'N/A';
        $transaction_date = $this->getCurrentDate();
        $type             = 'add';

        if ($this->id !== 'New') {
            // TODO: Load existing record into variables
        }

        ob_start();
        ?>
        <div class="row">
            <div class="col-md-12">
                <form id="myForm" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="related_id" id="related_id" value="<?= htmlspecialchars($this->id) ?>">
                    <input type="hidden" name="action" id="action" value="save">
                    <input type="hidden" name="form_token" value="<?= htmlspecialchars($form_token) ?>">
                    <input type="hidden" name="action_type" value="sector">
                    <input type="hidden" id="pageName" value="Opening-Sector-Advance">

                    <div class="card card-primary">
                        <div class="card-body">
                            <div class="row">

                                <!-- Sector Name -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="action_id">Sector Name</label>
                                        <select name="action_id" id="action_id" required class="form-control select2" style="width: 100%;">
                                            <option value="">Select One</option>
                                            <?php foreach ($List2->ListData() as $row): ?>
                                                <option value="<?= $row['id'] ?>" <?= $this->isSelected($action_id, $row['id']) ?>>
                                                    <?= htmlspecialchars($row['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Amount -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount">Amount</label>
                                        <input required type="number" step="0.01"
                                               value="<?= number_format($amount, 2, '.', '') ?>"
                                               class="form-control" name="amount" id="amount" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="remarks">Remarks</label>
                                        <input type="text" class="form-control" name="remarks" id="remarks"
                                               value="<?= htmlspecialchars($remarks) ?>" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Transaction Date -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transaction_date">Date</label>
                                        <input required type="date" class="form-control" name="transaction_date" id="transaction_date"
                                               value="<?= htmlspecialchars($transaction_date) ?>" autocomplete="off">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="card-footer">
                            <input type="submit" name="kt_submit_button" id="kt_submit_button"
                                   class="btn btn-primary" value="Submit">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }

    /** Dropdown helper */
    private function isSelected($value, $expected): string {
        return ($value == $expected) ? 'selected' : '';
    }

    /** Current date helper */
    private function getCurrentDate(): string {
        return date("Y-m-d");
    }
}
