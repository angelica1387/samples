<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Feb 20, 2018 
 * 
 */
/*
 * Download Adwords Stats across multiple clients, under unique Adwords Master Account 
 * Run at php-cli
 *  */


include_once __DIR__."/../config/config.php";
require __DIR__.'/Reports.php';
date_default_timezone_set('US/Eastern');

#Keyword Report
#Locations Report
#Campaign Report

$config = __DIR__."/../config/adsapi_php.ini";

$adsReport = new GoogleAdsReport($config);   

$dateRange = isset($argv[2])?$argv[2]:date('Ymd',strtotime("-1 days")).",".date('Ymd',strtotime("-1 days"));


set_time_limit(0);
while (($client = current($settings)) !== FALSE ) {   

    $franch = key($settings); 

	$adsReport->switchCostumerId($client["costumerId"]);  

	$adsReport->downloadClientPerformace($franch, $dateRange);	
	$adsReport->downloadCampaignsPerformace($franch, $dateRange);
	$adsReport->downloadGeoPerformance($franch, $dateRange);
	$adsReport->downloadKeywordPerformance($franch, $dateRange);
   
        sleep(10);
    next( $settings ); 

}


exit();