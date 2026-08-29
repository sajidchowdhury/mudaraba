<?php
include "autoloader.inc.php";

$type  = $_POST['type'] ?? '';
$month = $_POST['month'] ?? '';

$List  = new NewInvestor();
$List2 = new NewSector();

if ($type === 'Investor-Wise') {

    ob_start(); // capture HTML

    ?>
    <div class="col-md-6">
        <div class="form-group">
            <label for="investor_id">Investor Name</label>
            <select name="investor_id" id="investor_id" required class="form-control select2" style="width: 100%;">
                <option value="">Select One</option>
                <?php foreach ($List->ListData() as $row): 
                    $name = (!empty($row['reference'])) ? $row['name'] . ' (' . $row['reference'] . ')' : $row['name']; ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="sector_id">Sector Name</label>
            <select name="sector_id" id="sector_id" onchange="getSectorDue(this.value)" required class="form-control select2" style="width: 100%;">
                <option value="">Select One</option>
                <?php foreach ($List2->ListData() as $row): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="advance_profit">Adv. Profit</label>
            <input readonly type="number" step="0" value="0" class="form-control text-danger" name="advance_profit" id="advance_profit" autocomplete="off">
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="amount">Amount</label>
            <input required type="number" step="0" value="0" class="form-control" id="total_amount" autocomplete="off">
        </div>
    </div>

    <div class="col-md-12 mt-3">
        <button type="button" class="btn btn-success" onclick="saveBulkProfit()">Save Investor Wise</button>
    </div>
    <?php

    echo ob_get_clean();

} elseif ($type === 'As-Per-Invest') {

    $rows = $List->ListDataByDateRange($month);
    $List3 = new NewSector();

    $List2        = new SectorProfit();

    $TotalProfit  = $List2->MonthlyProfit($month);

    $inv              = new Investments();
    $ActualInvestment = $inv->TotalInvestment();

    $profitShares = [];
    foreach ($rows as $idx => $row) {
        $investment = $inv->TotalInvestmentByInvestor($row['id']);
        $ratio      = ($ActualInvestment > 0 ? $investment / $ActualInvestment : 0);
        $exact      = $ratio * $TotalProfit['estimatedprofit'];
        $actual     = $ratio * $TotalProfit['actualprofit'];
        $disb       = $exact;
        $profitShares[] = [
            'row'        => $row,
            'investment' => $investment,
            'ratio'      => $ratio,
            'disb'       => $disb,
            'actual'     => $actual,
            'deed'       => $row['profit']
        ];
    }

    ob_start(); ?>



        <div class="col-md-6">
        <div class="form-group">
            <label for="sector_id">Sector Name</label>
            <select name="sector_id" id="sector_id" onchange="getSectorDue(this.value)" required class="form-control select2" style="width: 100%;">
                <option value="">Select One</option>
                <?php foreach ($List3->ListData() as $row): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="advance_profit">Adv. Profit</label>
            <input readonly type="number" step="0" value="0" class="form-control text-danger" name="advance_profit" id="advance_profit" autocomplete="off">
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="total_amount">Total Amount</label>
            <input required type="number" step="0" value="0" class="form-control" id="total_amount" autocomplete="off" onkeyup="distributeAmount()">
        </div>
    </div>


    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Sl</th>
                    <th>Investor</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody id="investorTableBody">
                <?php foreach ($profitShares as $index => $ps): 
                    $name = (!empty($ps['row']['reference'])) ? $ps['row']['name'] . ' (' . $ps['row']['reference'] . ')' : $ps['row']['name']; ?>
                    
                    <input type="hidden" name="investment[]" value="<?= round($ps['investment']) ?>">
                    <input type="hidden" name="investment_ratio[]" value="<?= number_format($ps['ratio'], 6) ?>">
                    <input type="hidden" name="deed_ration[]" value="<?= $ps['deed'] ?>">

                    <tr data-investor_id="<?= $ps['row']['id'] ?>" data-ratio="<?= $ps['ratio'] ?>">
                        <td><?= $index + 1 ?></td>
                        <td>
                            <?= htmlspecialchars($name) ?><br>
                            <b class="text-danger">Inv = <?= formatNumber(round($ps['investment'])) ?></b><br>
                            <b class="text-info">Inv Ratio = <?= number_format($ps['ratio'], 6) ?>%</b>
                        </td>
                        <td>
                            <input type="number" step="0" class="form-control investor-amount" name="amounts[<?= $ps['row']['id'] ?>]" value="0">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">Total</th>
                    <th id="footer_total">0.00</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="col-md-12 mt-3">
        <button type="button" class="btn btn-success" onclick="saveBulkProfit()">Save Bulk</button>
    </div>
    <?php
    echo ob_get_clean();

} elseif ($type === 'Sector_Wise_Adv_Profit') {

 $sector_id = $_POST['sector_id'] ?? 0;
    $List3 = new SectorProfit();
    $due   = $List3->SectorProfitDue($sector_id);
    echo (float)$due;

} else {
    echo '';
}
