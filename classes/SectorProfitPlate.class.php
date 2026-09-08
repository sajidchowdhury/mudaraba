<?php

class SectorProfitPlate {
    private $id;

    public function __construct($id = 'New') {
        $this->id = $id;
    }

    public function SetupForm() {
        // Generate unique form token for CSRF protection
        $form_token = md5(uniqid(rand(), true));
        $_SESSION['form_tokens'][$form_token] = time();
        $csrf_token = $_SESSION['csrf_token'] ?? '';

        // Prepare sector list object
        $List = new NewSector();

        // Determine profit month for input[type=month] value (format YYYY-MM)
        if ($this->id !== 'New') {
            $profit_month = date('Y-m', strtotime($this->id)); // e.g. '2025-02'
        } else {
            $profit_month = date('Y-m'); // current month
        }

        // Initialize serial number for table rows
        $sl = 1;

        // Start building content


        $content = '
<div class="row">
    <div class="col-md-12">
        <div class="form-row align-items-end mb-4">
<div class="col-auto">
            <label for="profit_month"  class="font-weight-bold">Profit Month:</label>
          </div>
            <!-- Left: month input -->
            <div class="col-auto">
                <input required type="month" value="' . htmlspecialchars($profit_month) . '" 
                       class="form-control" name="profit_month" id="profit_month" autocomplete="off">
            </div>

            <!-- Center: title -->
            <div class="col text-center">
                <h4 class="font-weight-bold">
                    Sector Profit of Month: <b id="MonthName">' . htmlspecialchars(date("Y-m")) . '</b>
                </h4>
            </div>

            <!-- Right: print and link buttons -->
            <div class="col-auto">
                            <input type="submit" id="kt_submit_button" class="btn btn-primary" value="Save">

                <button type="button" class="btn btn-outline-secondary ml-2" onclick="window.print()">
                    🖨️ Print
                </button>
                <b id="LinkPD">
                    <a href="dynamic-page.php?page=Investor-Profit&id=' . htmlspecialchars($profit_month) . '" class="btn btn-outline-secondary ml-2">
                        Profit Disbursement of ' . htmlspecialchars($profit_month) . '
                    </a>
                </b>
            </div>

        </div>
        <!-- rest of your form here -->
';



        $content .= '
        <div class="row">
            <div class="col-md-12">
              

                <form id="myForm" method="post">
                    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">
                    <input type="hidden" name="related_id" id="related_id" value="' . htmlspecialchars($this->id) . '">
                    <input type="hidden" name="action" id="action" value="save">
                    <input type="hidden" id="form_token" name="form_token" value="' . htmlspecialchars($form_token) . '">

                    <div class="card card-primary">
                        <div class="card-body">
                            <div class="row">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Sector</th>
                                            <th>Disbursed Profit</th>
                                            <th>Actual Profit</th>
                                            <th>Adv. Profit</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

        // Loop through sector list and create table rows
        foreach ($List->ListData() as $row) {
            $content .= '
                <tr class="invoice-row" data-sector_id="' . htmlspecialchars($row['id']) . '">
                    <td>' . $sl++ . '</td>
                    <td>' . htmlspecialchars($row['name']) . '</td>
                    <td>
                        <input required type="number" step="0.01" onkeyup="CalCulateTotal();" 
                               value="0.00" class="form-control" name="est_amount[]" autocomplete="off">
                    </td>
                    <td>
                        <input required type="number" step="0.01" onkeyup="CalCulateTotal();" 
                               value="0.00" class="form-control" name="amount[]" autocomplete="off">
                    </td>

                    <td><b id="diff">0.00</b></td>
                </tr>';
        }


      
    

        // Footer rows for totals and profit month input
        $content .= '
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th>Total Profit</th>
                                            <th><b id="TotalEstProfit">0.00</b></th>
                                            <th><b id="TotalProfit">0.00</b></th>
                                            <th><b id="TotalDiff">0.00</b></th>
                                        </tr>
                                        
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th>
                                                
                                            </th>
                                            <th></th>        <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer"></div>
                    </div>
                </form>
            </div>
        </div>';

        print $content;
    }
}
