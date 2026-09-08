<?php

class AdvanceProfitAdjustmentTypeBPlate {
    private $id;

    public function __construct($id = 'New') {
        $this->id = $id;
    }

    public function SetupForm() {

$List2  = new NewSector();
$List3  = new SectorProfit();
$total_fund = 0;
$AdvBalance = new AdvanceTypeBDueManager();

$adv_profit_adjusting_fund = $AdvBalance->getTotalFund();

        // Security tokens
        $form_token = md5(uniqid(rand(), true));
        $_SESSION['form_tokens'][$form_token] = time();
        $csrf_token = $_SESSION['csrf_token'] ?? '';

        $List = new NewInvestor();
     


        $transaction_month = date("Y-m");



        // Start form HTML
     ?>
<div class="row">
    <div class="col-md-12">
        <form id="myForm" method="post">
            <input type="hidden" name="csrf_token" value="<?=  $csrf_token ;?>">
            <input type="hidden" id="form_token" name="form_token" value="<?= $form_token ;?>">
            <div class="card card-primary">
                <div class="card-body">
                    <div class="row">

                        <!-- Profit Type -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Invest Ratio</label>
                                <select name="type" id="type" required  onchange="InvestorRationWiseData(this.value,'TypeB')" class="form-control select2" style="width: 100%;">
                                    <option value="">Select One</option>

                                <?php 

                                foreach ($List->ListDataByInvestRatio() as $row) {  ?>

                                    <option value='<?= $row['profit'];?>'><?= $row['profit'] ?></option>
                              <?php   }

?>

                                  </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transaction_month">Month</label>
                                <input required type="month" class="form-control" name="transaction_month" id="transaction_month" value="<?= $transaction_month ;?>" autocomplete="off">
                            </div>
                        </div>


 <!-- Dynamic Content Area -->
                        <div class="row col-md-6">


<div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-light">
                <tr>
                    <th style="text-align:right;">Adv Profit Adjusting Fund</th>
                    <th colspan="2">
                        <input class="form-control text-danger" readonly type="number" id="adv_profit_adjusting_fund"
                               value="<?= number_format($adv_profit_adjusting_fund, 2, '.', '') ?>">
                    </th>
                </tr>
                <tr>
                    <th>Sector</th>
                    <th>Adjusted</th>
                </tr>
            </thead>
            <tbody id="sectorTableBody">
                <?php foreach ($List2->ListData() as $index => $ps):
                    $due = $List3->SectorProfitDue($ps['id']);
                ?>
                <tr data-sector_id="<?= $ps['id'] ?>">
                    <td>
                        <?= htmlspecialchars($ps['name']) ?><br>
                        <b class="text-danger">Balance = <?= number_format($due, 2) ?></b>
                    </td>
                    <!-- unique ID for each sector input -->
                    <td>
                        <input onkeyup="updateTotals()" id="sector_adjust_<?= $ps['id'] ?>" class="form-control sector_adjust" 
                               type="number" value="0.00" min="0" step="0.01">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="text-right">Total Adjust</th>
                    <th id="footer_total2">0.00</th>
                </tr>
                <tr>
                    <th class="text-right">Total Fund</th>
                    <th id="remaining_fund"><?= number_format($total_fund, 2, '.', '') ?>
                    </th>
                <input type="hidden" name="RemainingFund" id="RemainingFund" value="<?= number_format($total_fund, 2, '.', '') ?>"> 
                <input type="hidden" name="AllocatedFUnd" id="AllocatedFUnd" value="0.00"> 

                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-right mt-3">
        <input type="button" class="btn btn-success" id="save_bulk_btn" onclick="saveHandler()" value="Save Bulk"> 
    </div>


                        </div> <!-- End LoadTypeWiseData -->

                        <!-- Dynamic Content Area -->
                        <div id="LoadTypeWiseData" class="row col-md-6">
<input type="hidden"  type="number" id="adv_adjust" value="0.00" step="0.01" min="0">
                        </div> <!-- End LoadTypeWiseData -->
                        <!-- Remarks -->
                  

                        <!-- Month -->
                       

                    </div>
                </div>

                <div class="card-footer">
                </div>
            </div>
        </form>
    </div>
</div>

<?php     }

 
}
