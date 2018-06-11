<?php
/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Apr 11, 2017 
 * 
 */


require_once __DIR__ .'/../includes/vendor/autoload.php';
require_once __DIR__ .'/config.php';
require_once __DIR__ .'/../includes/MysqliDb.php';

define(MAX_BATCH_REQ, 10);

$yesterday = new Google_Service_AnalyticsReporting_DateRange();
$yesterday->setStartDate("yesterday");
$yesterday->setEndDate("yesterday");
	
//metrics
        //ga:users,ga:newUsers,ga:sessions,ga:bounceRate,ga:pageviewsPerSession,ga:avgSessionDuration
//dimensions
        //ga:hostname,ga:pagePath,ga:channelGrouping,ga:medium,ga:source,ga:keyword,ga:date
	
$users = new Google_Service_AnalyticsReporting_Metric();
$users->setExpression("ga:users");
$users->setAlias("users");

$newUsers = new Google_Service_AnalyticsReporting_Metric();
$newUsers->setExpression("ga:newUsers");
$newUsers->setAlias("newUsers");

$sessions = new Google_Service_AnalyticsReporting_Metric();
$sessions->setExpression("ga:sessions");
$sessions->setAlias("sessions");

$bounceRate = new Google_Service_AnalyticsReporting_Metric();
$bounceRate->setExpression("ga:bounceRate");
$bounceRate->setAlias("bounceRate");

$pageviewsPerSession = new Google_Service_AnalyticsReporting_Metric();
$pageviewsPerSession->setExpression("ga:pageviewsPerSession");
$pageviewsPerSession->setAlias("pageviewsPerSession");

$avgSessionDuration = new Google_Service_AnalyticsReporting_Metric();
$avgSessionDuration->setExpression("ga:avgSessionDuration");
$avgSessionDuration->setAlias("avgSessionDuration");
$metrics = array($users, $newUsers, $sessions,$bounceRate, $pageviewsPerSession, $avgSessionDuration  );

//Create the Dimensions objects.	
$hostname = new Google_Service_AnalyticsReporting_Dimension();
$hostname->setName("ga:hostname");

$pagePath = new Google_Service_AnalyticsReporting_Dimension();
$pagePath->setName("ga:pagePath");

$channelGrouping = new Google_Service_AnalyticsReporting_Dimension();
$channelGrouping->setName("ga:channelGrouping");

$medium = new Google_Service_AnalyticsReporting_Dimension();
$medium->setName("ga:medium");

$source = new Google_Service_AnalyticsReporting_Dimension();
$source->setName("ga:source");

$keyword = new Google_Service_AnalyticsReporting_Dimension();
$keyword->setName("ga:keyword");

$date = new Google_Service_AnalyticsReporting_Dimension();
$date->setName("ga:date");

$dimensions = array($hostname,$pagePath,$channelGrouping,$medium,$source,$keyword,$date);

	
foreach($service_credentials as $filename=>$account_credentials){	
	
	$KEY_FILE_LOCATION = __DIR__ . '/credentials/'.$filename.'.json';	
	$client = new Google_Client();
	$client->setAuthConfig($KEY_FILE_LOCATION);
	$client->addScope([Google_Service_Analytics::ANALYTICS_READONLY,
					Google_Service_Analytics::ANALYTICS_EDIT]);

        //Credentials to pull Analytics on behalf multiple accounts
        //Access previuosly granted
	$client->setDeveloperKey($account_credentials["API_key"]);
	$client->setSubject($account_credentials["email"]);  
	$client->refreshToken($account_credentials["refresh_token"]);

	$client->authorize(); 
	$batchClient = clone $client;
	$batchClient->setUseBatch(true);

	
	$analyticsReporting = new Google_Service_AnalyticsReporting($client);
	$analyticsRead = new Google_Service_Analytics($client);

	//Google_Service_Analytics($client);		
	$analyticsWrite = new Google_Service_Analytics($batchClient);		
	$batch = $analyticsWrite->createBatch();
	$accounts = $analyticsRead->management_accounts->listManagementAccounts();
	$requests = [];
	$pending_requests = [];
	$accounts = $accounts->getItems();	
	
	
	$con = new MysqliDb(null,null,null,$account_credentials["db"]);
	/*
         *   Accounts from Database      
         *  
         */
	$locList = [];
	
	$con->disconnect();
	
	$keys = array_column($locList, "locationId");
	$vals = array_column($locList, "google_ua");
	$stores = array_combine($keys, $vals); 
	
	do{
	 	 	$account = current($accounts);	 		

		 	$firstAccountId = $account->getId();
			
			$properties = $analyticsRead->management_webproperties
		        ->listManagementWebproperties($firstAccountId);

         	if (count($properties->getItems()) > 0) {
			      $items = $properties->getItems();
			      $firstPropertyId = $items[0]->getId();
				  $storeid = array_search($firstPropertyId, $stores);				  
				 
				  if(($storeid === FALSE) ){
					  next($accounts);
						continue;
				  }							
				
			      // Get the list of views (profiles) for the authorized user.
			      $profiles = $analyticsRead->management_profiles
			          ->listManagementProfiles($firstAccountId, $firstPropertyId);
					  
				//"All Web Site Data";
				
		      	if (count($profiles->getItems()) > 0) {
					
			        $items = $profiles->getItems();
					 
					 $websiteAll = array_filter($items,function($v){
                        return $v->name == "All Web Site Data";
                    });
                    if(count($websiteAll) == 0){
                        next($accounts);
						continue;
                    }

                    array_values($websiteAll);				
			        $profileId =  $websiteAll[0]->getId();
					
					$request = new Google_Service_AnalyticsReporting_ReportRequest();
					$request->setViewId($profileId);
					$request->setDateRanges(array($yesterday));
					$request->setDimensions($dimensions);
					$request->setMetrics($metrics);
					
					$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
					$body->setReportRequests( array( $request) );
					try{
							$reports = $analyticsReporting->reports->batchGet( $body );		
					}catch(Google_Service_Exception $ex){
						print_r($ex->getErrors());
						next($accounts);
						continue;
					}
				
					$newRow = [
                                                "client" => $storeid,
                                                "account_id" => $firstAccountId,
                                                "profile_id" => $profileId,
                                                "profile_name" => "All Web Site Data",
                                                "web_property_id" => $firstPropertyId,								
					];					
					getData($reports,  $newRow);
		      	} 
		    }     					
 	}while (next($accounts) !== FALSE); # Loop  Accounts	
}

function getData(&$reports, $newRow){
			
	$db_link = new MysqliDb(null,null,null,"[db_name]");
	
	for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
	
		$report = $reports[ $reportIndex ];
		$header = $report->getColumnHeader();
		$dimensionHeaders = $header->getDimensions();
		$metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
		$rows = $report->getData()->getRows();
		$valuesRows = [];
		for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
			
			$data = [];
			$row = $rows[ $rowIndex ];					
			$dimensions = $row->getDimensions();					
			$metrics = $row->getMetrics();
		
			$users= $new_users = $sessions = $bounce_rate = $pages_per_session = $avg_session_duration = 0;
			
			for ($j = 0; $j < count($metrics); $j++) {
				
				if(array_sum($metrics[$j]->getValues()) > 0.00)
					$values = $metrics[$j]->getValues();
			}
		
			$data = ["date" => $dimensions[6],
                                "hostname" => $dimensions[0],
                                "page_path" => $dimensions[1],
                                "channel_group" => $dimensions[2],
                                "medium" => $dimensions[3],
                                "source" => $dimensions[4],
                                "keyword" => $dimensions[5],
                                "users" => $values[0],
                                "new_users" => $values[1],
                                "sessions" => $values[2],
                                "bounce_rate" => $values[3],
                                "pages_per_session" => $values[4],
                                "avg_session_duration" => $values[5],
			];
			
			$valuesRows[] = array_merge($newRow, $data) ;	
			
			
		} # END FOR ROWS
		$db_link->insertMulti("ga_acquisitions_traffic",$valuesRows);
		unset($valuesRows);
	  }
		$db_link->disconnect();
			
}




