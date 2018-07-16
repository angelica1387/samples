<?php

require __DIR__.'/Reports.php';

require_once __DIR__."/../config/config.php";
date_default_timezone_set('US/Eastern');

error_reporting(E_ERROR);

##Keyword Report
#Locations Report
#Campaign Report

$config = __DIR__."/../config/adsapi_php.ini";
$adsReport = new GoogleAdsReport($config); 
$dateRange = isset($argv[2])?$argv[2]:date('Ymd',strtotime("-1 days")).",".date('Ymd',strtotime("-1 days"));
//$dateRange = isset($_GET["daterange"])?$_GET["daterange"]:date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));

set_time_limit(0);

if(isset($argv[1])  && $argv[1] != '0'){
	if(array_key_exists($argv[1],$settings)){
		 $settings = [$argv[1] => $settings[$argv[1]]];
	}else{
		$dates = explode(",",$argv[1]);
		$dateRange = strtotime($dates[0]) && strtotime($dates[1])?$argv[1]:$dateRange;

	}
       
}

while (($client = current($settings)) !== FALSE ) {
   
    $franch = key($settings); 

    $clientsIds = explode(",",$client["costumerId"]);
    foreach($clientsIds as $clientId ){        
        $adsReport->switchCostumerId($clientId);
    	$adsReport->downloadClientPerformace($clientId, $dateRange);
        $adsReport->downloadCampaignsPerformace($clientId, $dateRange);
       	$adsReport->downloadGeoPerformance($clientId, $dateRange);
        $adsReport->downloadKeywordPerformance($clientId, $dateRange);
        $adsReport->downloadSearchTermsPerformance($clientId, $dateRange);
    }    
    //sleep(10);
    next( $settings );    
}


exit();

