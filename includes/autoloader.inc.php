<?php 

spl_autoload_register('myAutoLoader');

function myAutoLoader($className){

$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

if (strpos($url, 'includes') !== false){
    $path =  "../classes/";
}else{
    $path =  "classes/";
}

$extension = ".class.php";
$fullPath = $path . $className . $extension ; 

if (!file_exists($fullPath)) {
   return false ; 
}
include_once $fullPath ; 

}   



 function formatNumber($num) {
    $num = (string)$num;
    $last3 = substr($num, -3);    // last 3 digits
    $rest = substr($num, 0, -3);  // everything except last 3

    if ($rest != '') {
        $last3 = ',' . $last3;
    }

    $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);

    return $rest . $last3;
}
