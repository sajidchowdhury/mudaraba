<?php
include_once "autoloader.inc.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<div class='alert alert-danger'>Invalid request method</div>";
    exit;
}

$report_type = $_POST['report_type'] ?? '';
$date_from   = $_POST['date_from'] ?? '';
$date_to     = $_POST['date_to'] ?? '';
$relatedid   = $_POST['relatedid'] ?? '';
$report_name = $_POST['report_name'] ?? '';


if (empty($report_type)) {
    echo "<div class='alert alert-danger'>Select Report Type</div>";
    exit;
}

$reportClasses = [
    'Investor Ledger Report'              => 'InvestorLedgerReportContr',
    'Sector Ledger Report'                => 'SectorLedgerReportContr',
    'Investment & Profit Report'          => 'InvestmentProfitReportContr',
    'MY Ledger Report'          => 'DirectorLedgerReportContr',
    'Advance Profit Adjustment Report'          => 'AdvanceProfitAdjustmentReportContr'


];

if (!array_key_exists($report_name, $reportClasses)) {
    echo "<div class='alert alert-danger'>Invalid report type</div>";
    exit;
}




$className = $reportClasses[$report_name];


$reportObj = new $className($report_type, $relatedid, $date_from, $date_to);

echo $reportObj->Report();
exit;
?>
