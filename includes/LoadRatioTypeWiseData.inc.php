<?php
include "autoloader.inc.php";

$Ratio  = $_POST['Ratio'] ?? '';
$Type   = $_POST['Type'] ?? '';
$month  = $_POST['month'] ?? '';

$List   = new NewInvestor();

$inv    = new Investments();
$ledger = new InvestorProfit();

 
$rows = $List->ListDataByDateRangeGroupByRatio($month, $Ratio);

$profitShares = [];
$Balance = 0;
$TotalInv = 0;

foreach ($rows as $idx => $row) {
    $investment = $inv->TotalInvestmentByInvestor($row['id']);

    
    $investorLedger = $ledger->InvestorWiseLedger($row['id']);

    $profitShares[] = [
        'id'          => $row['id'],
        'name'        => $row['name'],
        'reference'   => $row['reference'],
        'investment'  => $investment,
    ];

    $Balance  += $investorLedger;
    $TotalInv += $investment;
}

$total_fund =  $Balance;


?>

<!-- LEFT: Investors -->
<div class="col-md-12">
    <div id="profitAdjustContainer" class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-light">
                <tr>
                    <th style="text-align:right;">Adjustable Balance</th>
                    <th>
                        <input class="form-control text-danger" readonly type="number" id="adjustable_balance"
                               value="<?= number_format($Balance, 2, '.', '') ?>">
                    </th>
                </tr>
                <tr>
                    <th style="text-align:right;">Adv Profit Adjusting</th>
                    <th>
                        <!-- remove inline onkeyup; we'll bind in JS -->
                        <input class="form-control" onchange="Calculator()" type="number" id="adv_adjust" value="0.00" step="0.01" min="0">
                    </th>
                </tr>
                <tr>
                    <th>Investor (Ratio <?= htmlspecialchars($Ratio) ?>%)</th>
                    <th>Adjusting From Each</th>
                </tr>
            </thead>
            <tbody id="investorTableBody">
                <?php foreach ($profitShares as $index => $ps):
                    $name = (!empty($ps['reference'])) ? $ps['name'] . ' (' . $ps['reference'] . ')' : $ps['name'];
                    $rules = $ps['investment'] / ($TotalInv ?: 1);
                ?>
                <tr data-investor_id="<?= $ps['id'] ?>" data-rules="<?= $rules ?>">
                    <td>
                        <?= htmlspecialchars($name) ?><br>
                        <b class="text-danger">Inv = <?= number_format($ps['investment'], 2) ?></b><br>
                        <b class="text-info">Rules: <?= number_format($rules, 6) ?></b>
                    </td>
                    <!-- unique ID for investor amount -->
                    <td><b id="investor_amount_<?= $ps['id'] ?>" class="investor-amount">0.00</b></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="text-right">Total</th>
                    <th id="footer_total">0.00</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

