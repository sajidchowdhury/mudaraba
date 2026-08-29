<?php

class InvestmentsPlate {
    private $id;

    public function __construct($id = 'New') {
        $this->id = $id;
    }

    public function SetupForm() {


$form_token = md5(uniqid(rand(), true));
$_SESSION['form_tokens'][$form_token] = time();


        $List = new NewInvestor();

        // Generate unique form token for CSRF protection
        $form_token = md5(uniqid(rand(), true));
        $_SESSION['form_tokens'][$form_token] = time();
        $csrf_token = $_SESSION['csrf_token'] ?? ''; // Prevent undefined index error

        if ($this->id == 'New') {

            $investor_id = '';
            $amount  = 0.00;
            $remarks = 'N/A';
            $type = 'add';
            $transaction_date = $this->getCurrentDate();

        } else {

            $info = new Transaction();
            $data = $info->InvoiceDetails($this->id);
            $investor_id = $data['account_id'];
            $collection = ($data['transaction_type'] == 'credit') ? $data['amount'] : 0.00;
            $withdrawal = ($data['transaction_type'] == 'debit') ? $data['amount'] : 0.00;
            $remarks = $data['description'];
            $transaction_date = $data['transaction_date'];
            $transaction_type = $data['transaction_type'];
        }

        // Start Form Content
        $content = <<<HTML
        <div class="row">
            <div class="col-md-12">
                <form id="myForm" method="post">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <input type="hidden" name="related_id" id="related_id" value="{$this->id}">
                    <input type="hidden" name="action" id="action" value="save">
                    <input type="hidden" name="form_token" value="{$form_token}">



                    <div class="card card-primary">
                        <div class="card-body">
                            <div class="row">
                                <!-- Customer Name -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="investor_id">Invenstor Name</label>
                                        <select name="investor_id" id="investor_id" onchange="InvenstorDue(this.value);" required class="form-control select2" style="width: 100%;">
                                            <option value="">Select One</option>
        HTML;

        foreach ($List->ListData() as $row) {
            
            $name = (!empty($row['reference'])) ? $row['name'] . ' (' .$row['reference'].')' : $row['name'];

            $selected = ($investor_id == $row['id']) ? 'selected' : '';
            $content .= "<option value='{$row['id']}' {$selected}>{$name}</option>";
        }

        $content .= <<<HTML
                                        </select>
                                    </div>
                                </div>

                                     <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="current_inv">Current Investment</label>
                                        <input  type="number" READONLY step="0.01" value="" class="form-control text-danger" name="current_inv" id="current_inv" autocomplete="off">

                                    </div>
                                </div>


                                             <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type">Type</label>
                                        <select name="type" id="type" required class="form-control select2" style="width: 100%;">
                                            <option value="add">Add</option>
                                            <option value="withdraw">Withdraw</option>
                                               </select>
                                    </div>
                                </div>

        HTML;


    $content .= <<<HTML
    <div class="col-md-6">
        <div class="form-group">
            <label for="amount">Amount</label>
            <input required type="number" step="0.01" value="{$amount}" class="form-control" name="amount" id="amount" autocomplete="off">
        </div>
    </div>

    HTML;

        $content .= <<<HTML
                                <!-- Remarks -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="remarks">Remarks</label>
                                        <input type="text" class="form-control" name="remarks" id="remarks" value="{$remarks}" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Transaction By -->
                         

                    
                                <!-- Date -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transaction_date">Date</label>
                                        <input required type="date" class="form-control" name="transaction_date" id="transaction_date" value="{$transaction_date}" autocomplete="off">
                                    </div>
                                </div>


                            </div>
                        </div>

                        <div class="card-footer">
                            <input type="submit" name="kt_submit_button" id="kt_submit_button" class="btn btn-primary" value="Submit">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        HTML;

        print $content;
    }

    /**
     * Helper function to check selected value in dropdowns
     */
    private function isSelected($value, $expected) {
        return ($value == $expected) ? 'selected' : '';
    }

    /**
     * Helper function to get the current date
     */
    private function getCurrentDate() {
        return date("Y-m-d");
    }
}
