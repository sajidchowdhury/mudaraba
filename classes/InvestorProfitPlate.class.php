<?php
class InvestorProfitPlate {
    private $id;

    public function __construct($id = 'New') {
        $this->id = $id;
    }

    public function SetupForm() {


        $form_token = md5(uniqid(rand(), true));
        $_SESSION['form_tokens'][$form_token] = time();
        $csrf_token = $_SESSION['csrf_token'] ?? '';
        
      if ($this->id !== 'New') {
      $month = $this->id;  // e.g. '2025-02'
      // Convert to correct format for <input type="month">
      $month = date('Y-m', strtotime($month)); // '2025-02'
      } else {
      $month = date('Y-m');  // current month in 'YYYY-MM' format
      }



        $List1 = new NewInvestor();
        $List2 = new SectorProfit();
        $TotalProfit = $List2->MonthlyProfit($month);

        $inv = new Investments();
        $ActualInvestment = $inv->TotalInvestment();
        $rows = $List1->ListDataByDateRange($month);
        ob_start();
        ?>
<style>

  table#example input.split-field {
  width: 100%;
  min-width: 60px;  /* prevent too narrow */
  max-width: 120px; /* reasonable max */
  box-sizing: border-box;
}


@media print {
    .btn, #customSplitBox, #splitWarning { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
}

@media (max-width: 576px) {
  /* On xs screens, inputs get full width with min width */
  table#example input.split-field {
    width: 100% !important;
    min-width: 70px !important;
    max-width: 100% !important;
  }
}
</style>

<div class="container-fluid">
  <div class="card mt-4 shadow-sm">
    <div class="card-body">
      <form id="myForm" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="related_id" value="<?= htmlspecialchars($this->id) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="form_token" value="<?= $form_token ?>">
        <input type="hidden" id="estimatedprofit" value="<?= $TotalProfit['estimatedprofit'] ?>">
        <input type="hidden" id="actualprofit" value="<?= $TotalProfit['actualprofit'] ?>">

        <div class="form-row align-items-center mb-4">

  <!-- Month label and input grouped -->
  <div class="col-12 col-sm-auto mb-2 mb-sm-0 d-flex align-items-center">
    <label for="profit_month" class="font-weight-bold mb-0 mr-2">Month:</label>
    <input type="month" class="form-control" name="profit_month" id="profit_month"
           value="<?= htmlspecialchars($month) ?>" required autocomplete="off" style="min-width: 140px;">
  </div>

  <!-- Center title and profit info -->
  <div class="col-12 col-sm text-center mb-2 mb-sm-0">
    <h4 class="font-weight-bold mb-1">
      Profit Disbursement : <b id="MonthName"><?= htmlspecialchars(date('Y-m')) ?></b>
    </h4>
    <div>
      <strong id="PrintEst">Est. Profit: <?= number_format($TotalProfit['estimatedprofit']) ?> TK </strong><br>
      <strong id="PrintAct">Actual Profit: <?= number_format($TotalProfit['actualprofit']) ?> TK </strong>
      <span id="splitWarning" class="text-danger ml-2 font-weight-bold"></span>
    </div>
  </div>

  <!-- Buttons -->
  <div class="col-12 col-sm-auto d-flex justify-content-sm-end">




    <button type="button" class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0" onclick="window.print()">🖨️ Print</button>
    <b id="LinkPD"><a href="dynamic-page.php?page=Sector-Profit&id=<?= urlencode($month); ?>" class="btn btn-outline-secondary mb-2 mb-sm-0">
      Sector Profit of <?= htmlspecialchars($month); ?>
    </a></b>
  </div>


</div>


        <div id="customSplitBox" class="card border-primary mb-4 p-3 d-none">
          <h6 class="font-weight-bold">Custom Profit Split Rules</h6>
          <div class="table-responsive mb-2">
            <table class="table table-bordered table-sm">
              <thead class="thead-light">
                <tr>
                  <th>SL</th>
                  <th>Disbursement %</th>
                  <th>No. of Investors</th>
                  <th><button type="button" class="btn btn-success btn-sm" onclick="addCustomSplitRow()">➕</button></th>
                </tr>
              </thead>
              <tbody id="customSplitTableBody"></tbody>
            </table>
          </div>
          <div>
            <button type="button" class="btn btn-primary btn-sm" onclick="applyCustomSplit()">✅ Apply</button>
            <span id="customSplitWarning" class="text-danger ml-2 font-weight-bold"></span>
          </div>
        </div>

        <div class="table-responsive">
          <table id="example" class="table table-striped table-bordered">
            <thead class="thead-light">
              <tr>
                <th>Sl</th>
                <th>Investor</th>
                <th>Disbursement Profit</th>
                <th>Actual Profit </th>
                <th>Deed % </th>
                <th>Profit as per deed % </th>
                <th>Advance Paid</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $profitShares = [];
              foreach ($rows as $idx => $row) {
                $investment = $inv->InvestmentTillMonth($row['id'],$month);
                $ratio = ($ActualInvestment > 0 ? $investment / $ActualInvestment : 0);
                $exact = $ratio * $TotalProfit['estimatedprofit'];
                $actual = $ratio * $TotalProfit['actualprofit'];
                $disb = $exact;
                $profitShares[] = [
                'row' => $row, 
                'investment' => $investment,
                'ratio' => $ratio, 
                'disb' => $disb, 
                'actual' => $actual,
                'deed' => $row['profit']
                ];
              }

              foreach ($profitShares as $index => $ps) {


                $isLast = ($index === array_key_last($profitShares));
                $est = round($ps['disb']);
                $actRatio = round($ps['actual']);
                if ($isLast) { $est = round($ps['disb']); }
                $h = round($actRatio * $ps['deed'] / 100);
                $diff = $est - $h;

                $name = (!empty($ps['row']['reference'])) ? $ps['row']['name'] . ' (' .$ps['row']['reference'].')' : $ps['row']['name'];

                ?>
                <tr data-investor_id="<?= $ps['row']['id'] ?>">
                  <input type="hidden" name="investment[]" value="<?= round($ps['investment']) ?>">
                  <input type="hidden" name="investment_ratio[]" value="<?= number_format($ps['ratio'],6) ?>">
                  <input type="hidden" name="estimated_profit[]" value="<?= $est ?>">
                  <input type="hidden" name="actual_profit[]" value="<?= $h ?>">
                  <input type="hidden" name="advance_paid[]" value="<?= $diff ?>">
                  <input type="hidden" name="deed_ration[]" value="<?= $ps['deed'] ?>">


                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($name) ?></b><br>
                    <b class="text-danger">Inv (c) = <?= formatNumber($ps['investment']) ?></b><br>
                    <b class="text-info">Inv Ratio (d) = <?= number_format($ps['ratio'],6) ?>%</b>
                  </td>
                  <td><?= formatNumber($est) ?></td>
                  <td><?= formatNumber($actRatio) ?>    <input type="hidden" name="actual_profit_before_deed[]" value="<?= $actualProfit;?>">
</td>
                  <td><?= $ps['deed'] ?></td>

                 
                  <td><?= formatNumber($h) ?></td>
                  <td><?= formatNumber($diff) ?></td>
                </tr>
              <?php } ?>
            </tbody>
            <tfoot>
              <?php
              // Totals
    $sumInv = array_sum(array_column($profitShares, 'investment'));

$sumEst = array_sum(array_map(function($p) {
    return round($p['disb']);
}, $profitShares));

$sumAct = array_sum(array_map(function($p) {
    return round($p['actual']);
}, $profitShares));

$sumH = array_sum(array_map(function($p) {
    return round($p['actual'] * $p['deed'] / 100);
}, $profitShares));

$sumDiff = $sumEst - $sumH;
              ?>
              <tr>
                <th colspan="2">Total<br><small>Inv = <?= formatNumber($sumInv) ?>, Ratio ≈ <?= number_format(array_sum(array_column($profitShares,'ratio')),3) ?>%</small></th>
                <th><?= formatNumber($sumEst) ?></th>
                <th><?= formatNumber($sumAct) ?></th>
                <th></th>
                <th><?= formatNumber($sumH) ?></th>
                <th><?= formatNumber($sumDiff) ?></th>
              </tr>
              <?php
$leftProfit = 0;
$myAmount = $TotalProfit['actualprofit'] - $sumH;

$a = $List2->MonthlySectorReceivablePayable($month);

$TotalReceDiff = 0;
$TotalPayDiff = 0;
?>


        

<tr>
    <th></th>
    <th colspan="6" class="text-danger">*** Receivable From Investors ***</th>
</tr>
<tr>
    <th></th>
    <td>M/Y</td>
    <td colspan="5"><?= formatNumber($myAmount) ?><input type="hidden" id="MyAmount"  name="MyAmount" value="<?= $myAmount ?>"></td>
</tr>

<?php foreach ($a['receivable_from_investors'] as $data): ?>
    <tr>
        <th></th>
        <td><?= $data['sector_name'] ?></td>
        <td colspan="5"><?= abs($data['difference']) ?></td>
    </tr>
    <?php $TotalReceDiff += abs($data['difference']); ?>
<?php endforeach; ?>

<?php $TotalReceivable = $myAmount + $TotalReceDiff; ?>
<tr>
    <th></th>
    <th>Total</th>
    <th colspan="5"><?= formatNumber($TotalReceivable) ?></th>
</tr>

<tr>
    <th></th>
    <th colspan="6" class="text-danger">*** Payable To Investors ***</th>
</tr>

<?php foreach ($a['payable_to_investors'] as $data): ?>
    <tr>
        <th></th>
        <td><?= $data['sector_name'] ?></td>
        <td colspan="5"><?= abs($data['difference']) ?></td>
    </tr>
    <?php $TotalPayDiff += abs($data['difference']); ?>
<?php endforeach; ?>

<tr>
    <th></th>
    <th>Total</th>
    <th colspan="5"><?= formatNumber($TotalPayDiff) ?></th>
</tr>

            </tfoot>
          </table>
        </div>

      </form>
    </div>
  </div>
</div>
        <?php
        print ob_get_clean();
    }
}
