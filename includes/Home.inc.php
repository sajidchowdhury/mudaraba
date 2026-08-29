<?php
header('Content-Type: application/json');
require_once 'autoloader.inc.php';


$data1 = new Investments();
$TotalCollection = $data1->TotalInvestment();

$data2 = new InvestmentAllocation();
$TotalInvestment = $data2->AllInvestment();

$left = $TotalCollection - $TotalInvestment;
$left = $left > 0 ? $left : 0;

// Sector wise investment
$data3 = new InvestmentAllocation();
$SectorWiseInvestment = $data3->SectorWiseTotalInvestment();

// Build donut data dynamically
$colors = ["#3c8dbc", "#0073b7", "#00c0ef", "#f39c12", "#00a65a", "#f56954"]; 
$donut = [];

$colorIndex = 0;
foreach ($SectorWiseInvestment as $sector) {
  
  

    $donut[] = [
        "label" => $sector['sectorName'],
        "data"  => $sector['total_investment'],
        "color" => $colors[$colorIndex % count($colors)]
    ];
    $colorIndex++;
}

$response = [
    "summary" => [
        "collection" => formatNumber($TotalCollection),
        "investment" => formatNumber($TotalInvestment),
        "left" => formatNumber($left)
    ],
    "donut" => $donut
];

echo json_encode($response);
