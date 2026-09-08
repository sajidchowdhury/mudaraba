<?php
// dynamic-page.php

require_once 'includes/config.inc.php';
require_once 'includes/autoloader.inc.php';

$page = $_GET['page'] ?? 'dashboard'; // default fallback

$page = basename($page); // removes path traversal
$pageFile = $page . '.php';


// Optional: whitelist allowed pages

$allowed_pages = ['Home','New-Investor','List-Investor','New-Sector','List-Sector','Investments','Sector-Profit','Investor-Profit','Investor-Ledger','MY-Ledger','Sector-Ledger','Sector-Wise','Advance-Profit-Adjustment-Type-A','Advance-Profit-Adjustment-Type-B','Investment-Profit','New-Director','List-Director','MY-Withdraw','Profit','Advance-Profit-Adjustment-Report','Opening-Amount-MY','Opening-Sector-Advance','Opening-Investor-Advance'];   // expand as needed


if (!in_array($page, $allowed_pages)) {
    die('Invalid Page');
}

// Build expected class name and file naming
$className = str_replace('-', '', $page) . 'Plate'; 
$pageTitle = str_replace('-', ' ', $page); 

// Module-wise script mapping
$moduleScripts = [

    'Opening-Amount-MY' => [


    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'plugins/toastr/toastr.min.js',
    'js/DataTable.js'  ,
    'js/select2.js'  ,
    'js/Opening.js'  


],

'Opening-Sector-Advance' => [


    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'plugins/toastr/toastr.min.js',
    'js/DataTable.js'  ,
    'js/select2.js'  ,
    'js/Opening.js'  


],

'Opening-Investor-Advance' => [


    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'plugins/toastr/toastr.min.js',
    'js/DataTable.js'  ,
    'js/select2.js'  ,
    'js/Opening.js'  


],

'Home' => [
'plugins/chart.js/Chart.min.js',
'plugins/flot/jquery.flot.js',
'plugins/flot-old/jquery.flot.resize.min.js',
'plugins/flot-old/jquery.flot.pie.min.js',
'js/Home.js'
] ,

    'Profit' => [
    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'js/DataTable.js'  ,
    'js/select2.js',
    'js/Profile.js'
    ]


    ,


    
                'New-Investor' => [
                'plugins/sweetalert2/sweetalert2.min.js',  
                'plugins/jquery-ui/jquery-ui.js',
                'plugins/datatables/jquery.dataTables.min.js',
                'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                'plugins/datatables-responsive/js/dataTables.responsive.min.js',
                'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
                'plugins/select2/js/select2.full.min.js',
                'js/DataTable.js'  ,
                'js/select2.js'  ,
                'js/NewInvestor.js' 

]    

,

                'List-Director' => [
                'plugins/sweetalert2/sweetalert2.min.js',  
                'plugins/jquery-ui/jquery-ui.js',
                'plugins/datatables/jquery.dataTables.min.js',
                'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                'plugins/datatables-responsive/js/dataTables.responsive.min.js',
                'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
                'plugins/select2/js/select2.full.min.js',
                'js/DataTable.js'  ,
                'js/select2.js'  

]  ,

                'List-Investor' => [
                'plugins/sweetalert2/sweetalert2.min.js',  
                'plugins/jquery-ui/jquery-ui.js',
                'plugins/datatables/jquery.dataTables.min.js',
                'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                'plugins/datatables-responsive/js/dataTables.responsive.min.js',
                'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
                'plugins/select2/js/select2.full.min.js',
                'js/DataTable.js'  ,
                'js/select2.js'  

]   ,

                'New-Sector' => [
                'plugins/sweetalert2/sweetalert2.min.js',  
                'plugins/jquery-ui/jquery-ui.js',
                'plugins/datatables/jquery.dataTables.min.js',
                'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                'plugins/datatables-responsive/js/dataTables.responsive.min.js',
                'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
                'plugins/select2/js/select2.full.min.js',
                'js/DataTable.js'  ,
                'js/select2.js'  ,
                'js/NewSector.js' 

]     ,

                'New-Director' => [
                'plugins/sweetalert2/sweetalert2.min.js',  
                'plugins/jquery-ui/jquery-ui.js',
                'plugins/datatables/jquery.dataTables.min.js',
                'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                'plugins/datatables-responsive/js/dataTables.responsive.min.js',
                'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
                'plugins/select2/js/select2.full.min.js',
                'js/DataTable.js'  ,
                'js/select2.js'  ,
                'js/NewDirector.js' 

]    

,

                'List-Sector' => [
                'plugins/sweetalert2/sweetalert2.min.js',  
                'plugins/jquery-ui/jquery-ui.js',
                'plugins/datatables/jquery.dataTables.min.js',
                'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                'plugins/datatables-responsive/js/dataTables.responsive.min.js',
                'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
                'plugins/select2/js/select2.full.min.js',
                'js/DataTable.js'  ,
                'js/select2.js' 

],

'Investments' => [

'plugins/sweetalert2/sweetalert2.min.js',  
'plugins/jquery-ui/jquery-ui.js',
'plugins/datatables/jquery.dataTables.min.js',
'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
'plugins/datatables-responsive/js/dataTables.responsive.min.js',
'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
'plugins/select2/js/select2.full.min.js',
            'plugins/toastr/toastr.min.js',
'js/DataTable.js'  ,
'js/select2.js'  ,
'js/Investments.js'  


],

'MY-Withdraw' => [


    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'plugins/toastr/toastr.min.js',
    'js/DataTable.js'  ,
    'js/select2.js'  ,
    'js/WithdrawByDirector.js'  


],

'Sector-Wise' => [

'plugins/sweetalert2/sweetalert2.min.js',  
'plugins/jquery-ui/jquery-ui.js',
'plugins/datatables/jquery.dataTables.min.js',
'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
'plugins/datatables-responsive/js/dataTables.responsive.min.js',
'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
'plugins/select2/js/select2.full.min.js',
            'plugins/toastr/toastr.min.js',
'js/DataTable.js'  ,
'js/select2.js'  ,
'js/InvestmentAllocation.js'  



],
'Sector-Profit' => [

    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'js/DataTable.js'  ,
    'js/select2.js'  ,
    'js/SectorProfit.js' 
]  ,

'Investor-Profit' => [

    'plugins/sweetalert2/sweetalert2.min.js',  
    'plugins/jquery-ui/jquery-ui.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'plugins/select2/js/select2.full.min.js',
    'js/DataTable.js'  ,
    'js/select2.js'  ,
    'js/InvestorProfit.js' 
],
'Investor-Ledger' => [
    'plugins/select2/js/select2.full.min.js',
    'plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js',
    'plugins/moment/moment.min.js',
    'plugins/inputmask/min/jquery.inputmask.bundle.min.js',
    'plugins/daterangepicker/daterangepicker.js',
    'js/gijgo.min.js',
    'plugins/sweetalert2/sweetalert2.min.js',
    'plugins/toastr/toastr.min.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'js/datepicker.js',
    'js/DataTable.js'  ,
        'js/select2.js'  ,
    'js/AllReport.js'


],
'MY-Ledger' => [
    'plugins/select2/js/select2.full.min.js',
    'plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js',
    'plugins/moment/moment.min.js',
    'plugins/inputmask/min/jquery.inputmask.bundle.min.js',
    'plugins/daterangepicker/daterangepicker.js',
    'js/gijgo.min.js',
    'plugins/sweetalert2/sweetalert2.min.js',
    'plugins/toastr/toastr.min.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'js/datepicker.js',
    'js/DataTable.js'  ,
        'js/select2.js'  ,
    'js/AllReport.js'


],
'Sector-Ledger' => [

    'plugins/select2/js/select2.full.min.js',
    'plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js',
    'plugins/moment/moment.min.js',
    'plugins/inputmask/min/jquery.inputmask.bundle.min.js',
    'plugins/daterangepicker/daterangepicker.js',
    'js/gijgo.min.js',
    'plugins/sweetalert2/sweetalert2.min.js',
    'plugins/toastr/toastr.min.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'js/datepicker.js',
    'js/DataTable.js'  ,
            'js/select2.js'  ,
    'js/AllReport.js'


] ,
'Advance-Profit-Adjustment-Report' => [

    'plugins/select2/js/select2.full.min.js',
    'plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js',
    'plugins/moment/moment.min.js',
    'plugins/inputmask/min/jquery.inputmask.bundle.min.js',
    'plugins/daterangepicker/daterangepicker.js',
    'js/gijgo.min.js',
    'plugins/sweetalert2/sweetalert2.min.js',
    'plugins/toastr/toastr.min.js',
    'plugins/datatables/jquery.dataTables.min.js',
    'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
    'js/datepicker.js',
    'js/DataTable.js'  ,
            'js/select2.js'  ,
    'js/AllReport.js'


]    ,

'Advance-Profit-Adjustment-Type-A' => [
'plugins/sweetalert2/sweetalert2.min.js',  
'plugins/jquery-ui/jquery-ui.js',
'plugins/datatables/jquery.dataTables.min.js',
'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
'plugins/datatables-responsive/js/dataTables.responsive.min.js',
'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
'plugins/select2/js/select2.full.min.js',
'plugins/toastr/toastr.min.js',
'js/DataTable.js'  ,
'js/select2.js'  ,
'js/AdvanceProfitTypeA.js'  

],

'Advance-Profit-Adjustment-Type-B' => [
'plugins/sweetalert2/sweetalert2.min.js',  
'plugins/jquery-ui/jquery-ui.js',
'plugins/datatables/jquery.dataTables.min.js',
'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
'plugins/datatables-responsive/js/dataTables.responsive.min.js',
'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
'plugins/select2/js/select2.full.min.js',
'plugins/toastr/toastr.min.js',
'js/DataTable.js'  ,
'js/select2.js'  ,
'js/AdvanceProfitTypeB.js'  

],

'Investment-Profit' => [
'plugins/sweetalert2/sweetalert2.min.js',  
'plugins/jquery-ui/jquery-ui.js',
'plugins/datatables/jquery.dataTables.min.js',
'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
'plugins/datatables-responsive/js/dataTables.responsive.min.js',
'plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
'plugins/select2/js/select2.full.min.js',
            'plugins/toastr/toastr.min.js',
'js/DataTable.js'  ,
'js/select2.js'  ,
'js/AllReport.js'

]




];

$scripts = $moduleScripts[$page] ?? [];

$siteStructure = new SiteStructure($scripts, $page, "$pageTitle,<a class='toastsDefaultBottomLeft' href='#'> $pageTitle সম্পর্কে জানতে ক্লিক করুন </a>");


if (!class_exists($className)) {
    die("Class not found: $className");
}
$id = $_GET['id'] ?? 'New';
$Form = new $className($id);
?>
<html>
<?= $siteStructure->head(); ?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?= $siteStructure->TopNav(); ?>
    <?= $siteStructure->PageSidebar($_SESSION['employee_id']); ?>
    <div class="content-wrapper">
        <?= $siteStructure->Setbreadcrumb(); ?>
        <section class="content">
            <div class="container-fluid">
                <?= $Form->SetupForm(); ?>
            </div>
        </section>
    </div>
    <?= $siteStructure->footer(); ?>
</div>
<?= $siteStructure->includeScripts(); ?>
</body>
</html>
