<?php
// includes/fetchInvestorProfitDetails.inc.php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once 'autoloader.inc.php'; // your autoloader - ensure path is correct

    $month = $_GET['month'] ?? '';
    // basic validation: YYYY-MM
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        throw new Exception('Invalid month format. Use YYYY-MM.');
    }

    // load classes (these names are from your project)
    $sectorProfit = new SectorProfit();
    $investments = new Investments();
    $newInvestor = new NewInvestor();
    $invProfitModel = new InvestorProfit(); // if you have investor_monthly_profit_details access wrapper

    // 1) monthly totals
    $TotalProfit = $sectorProfit->MonthlyProfit($month);
    $estimated = isset($TotalProfit['estimatedprofit']) ? (float)$TotalProfit['estimatedprofit'] : null;
    $actual = isset($TotalProfit['actualprofit']) ? (float)$TotalProfit['actualprofit'] : null;

    // 2) Monthly sector receivable/payable
    $receivablePayable = $sectorProfit->MonthlySectorReceivablePayable($month);



    // 2) all active investors for the month (by their start/end profit month)
    $rows = $newInvestor->ListDataByDateRange($month); // returns array of investors

    // 3) total investment
    $totalInvestment = (float) $investments->TotalInvestmentTillMonth($month);

    // 4) fetch existing saved details for that month
    // use model method MonthlyProfitDetails($month) which returns saved rows
    $savedRows = $invProfitModel->MonthlyProfitDetails($month); // array rows keyed by investor_id would be easier
    $savedIndex = [];
    foreach ($savedRows as $s) {
        $savedIndex[$s['investor_id']] = $s;
    }

    $investorList = [];

    foreach ($rows as $row) {
        $invId = (int)$row['id'];
        $investAmt = (float)$investments->InvestmentTillMonth($invId,$month);

        // compute ratio
        $ratio = $totalInvestment > 0 ? ($investAmt / $totalInvestment) : 0;

        // compute fields only when estimated/actual are not null
        $estimated_disbursement_e = null;
        $actual_share_f = null;
        $profit_h = null;
        $advance_i = null;

        if ($estimated !== null) {
            $estimated_disbursement_e = $estimated * $ratio;
        }

        if ($actual !== null && $actual > 0) {
            $actual_share_f = $actual * $ratio;
        }

        // Use deed ratio value from saved entry or investor's deed (assumes 'profit' column on investor row)
        $deed_ratio_g = isset($row['profit']) ? (float)$row['profit'] : 0;

        if ($actual_share_f !== null) {
            // calculate profit h and advance i
            $profit_h = round($actual_share_f * $deed_ratio_g / 100);
            // for advance need estimated (if estimated missing, set advance null)
            if ($estimated_disbursement_e !== null) {
                $advance_i = round($estimated_disbursement_e - $profit_h);
            } else {
                $advance_i = null;
            }
        }

        $saved = isset($savedIndex[$invId]);
        $saved_row_id = $saved ? $savedIndex[$invId]['id'] : null;


        $name = (!empty($row['reference'])) ? $row['name'] . ' (' .$row['reference'].')' : $row['name'];

        // If saved, prefer saved values (to show EXACT what was stored earlier)
        if ($saved) {
            $s = $savedIndex[$invId];
            // expected columns on saved table: estimated_profit, actual_profit, deed_ratio, advance_paid
            $investorList[] = [
                'investor_id' => $invId,
                'investor_name' => $name,
                'start_profit_month' => $row['start_profit_month'] ?? null,
                'end_profit_month' => $row['end_profit_month'] ?? null,
                'investment' => (float)$investAmt,
                'investment_ratio' => (float)$ratio,
                'estimated_disbursement_e' => $estimated_disbursement_e,
                'actual_share_f' =>  $actual_share_f,
                'deed_ratio_g' =>$deed_ratio_g,
                'profit_h' =>  $profit_h,
                'advance_i' =>  $advance_i,
                'saved' => true,
                'saved_row_id' => $saved_row_id
            ];
        } else {
            // unsaved computed
            $investorList[] = [
                'investor_id' => $invId,
                'investor_name' => $name ,
                'start_profit_month' => $row['start_profit_month'] ?? null,
                'end_profit_month' => $row['end_profit_month'] ?? null,
                'investment' => (float)$investAmt,
                'investment_ratio' => (float)$ratio,
                'estimated_disbursement_e' => $estimated_disbursement_e,
                'actual_share_f' => $actual_share_f,
                'deed_ratio_g' => $deed_ratio_g,
                'profit_h' => $profit_h,
                'advance_i' => $advance_i,
                'saved' => false,
                'saved_row_id' => null
            ];
        }
    }

    $payload = [
        'month' => $month,
        'estimatedprofit' => $estimated,
        'actualprofit' => $actual,
        'total_investment' => $totalInvestment,
        'investors' => $investorList,
        'receivablepayable'=> $receivablePayable
    ];

    echo json_encode(['success' => true, 'details' => $payload], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
