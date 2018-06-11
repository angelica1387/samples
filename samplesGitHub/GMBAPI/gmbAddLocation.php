<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=May 11, 2018 
 * 
 */
$client = new Google_Client();

$client->setApplicationName($gmb_client_settings["aplicattion_name"]);
$client->setDeveloperKey($gmb_client_settings["developerToken"]);
$client->setAuthConfig($credentials);  
$client->setScopes("https://www.googleapis.com/auth/plus.business.manage");
$client->setSubject($account["email"]); 
$client->refreshToken($account["refresh_token"]);
$client->authorize();

$accountId = "accounts/".$account["account_id"];

$mybusinessService = new Google_Service_Mybusiness($client);
$name = $loc["displayname"] ; 


$accounts = $mybusinessService->accounts->listAccounts()->getAccounts();
$locations =  $mybusinessService->accounts_locations;
$candidateAcct = "";	
$maps_cid_structure = "https://maps.google.com/maps?cid=";
foreach ($accounts as $act){
           
    $gmblisting = $act->getAccountName();
    $account_id = $act->getName();
      
  	if(($gmblisting  != $config_client["gmb_acct_name"]) && ($gmblisting != "Extra Locations")){          		
  		continue;
  	}
 	
	$params = ['pageSize' => 100];
    do{
    	
    	if(isset($listLocationsResponse) && ($listLocationsResponse->getNextPageToken() != "")){
    		$params["pageToken"] = $listLocationsResponse->getNextPageToken();
    	}

    	$listLocationsResponse = $locations->listAccountsLocations($account_id,$params);      
    	      
        if(($candidateAcct == "") && (count($listLocationsResponse->getLocations()) < 100 ) && 
        	( ($gmblisting  == $config_client["gmb_acct_name"]) || ($gmblisting == "Extra Locations"))){
        	$candidateAcct  = $account_id;
        	$candicateActName = $gmblisting;
        }
       
        foreach ($listLocationsResponse->getLocations() as $location) {
        	if($storeCode  == $location->getStoreCode()){
        			
                    $endpoint = split('/',$location->getName());            
                    $update_data = ["store_id" =>$store_id,
                    				"location_id" => $endpoint[3],
                    				"account_id" => $endpoint[1],
                    				"listing_name" => $gmblisting,
                    				"parent_account" => $account["account_id"],
				                    "client" => $config_client["client"],
				                    "name" => $location->getLocationName(),
				                    "place_id" => $location->getLocationKey()->getPlaceId(),
				                    "is_verified" => ($location->getLocationState()->getIsVerified())?1:0,
				                    "is_closed" => ($location->getOpenInfo()->getStatus() == "OPEN")?0:1,
				                    "plusPage_id" => $location->getLocationKey()->getPlusPageId(),
				                    "isLocalPostApiDisabled" => ($location->getLocationState()->getIsLocalPostApiDisabled())?1:0,
				                    "is_published" => ($location->getLocationState()->getIsPublished())?1:0,
									"cid" => str_replace($maps_cid_structure,"",$location->getMetadata()->getMapsUrl())
								];
                    //update table if exists
                        $db_link->replace("facebook_post.gmb_locations",$update_data );
				
                    $errors[] = "Location ".$location->getStoreCode()." exists at ".$gmblisting;
				notify($errors, $store_id, $listing);
				exit();
        	}
        	
        }

    }while($listLocationsResponse->getNextPageToken() != "");
}

if($candidateAcct == ""){
	$errors[] = "Something went wrong, a new listing needs to be created manually at Google My Business ".ucfirst($listing);
	notify($errors, $store_id, $listing);
	exit();
}

if($loc['phone'] != ""){
	$phone  = $loc['phone'];
}

#Postal Address 
$address[] = $loc["address"];
if(isset($loc["address2"]) && ($loc["address2"] != "")){
	$address[] = $loc["address2"];
}
$city = $loc["city"];
$state =  $loc["state"];
$zip = $loc['zip'];
$regionCode = "US";

$postalAddress =  new Google_Service_MyBusiness_PostalAddress();
$postalAddress->setAddressLines($address);
$postalAddress->setPostalCode($zip);
$postalAddress->setLocality($city);
$postalAddress->setAdministrativeArea($state);
$postalAddress->setRegionCode($regionCode);
/*$postalAddress->setLanguageCode("en");*/

#Primary Category
$categoryIds = $config_client["gmb_categories"];
$primaryCategory = new Google_Service_MyBusiness_Category();

//$primaryCategory->setCategoryId("gcid:software_company");
$primaryCategory->setCategoryId(array_shift($categoryIds));

#Additional Category

$additional_categories = [];

foreach ($categoryIds as $value) {
	$category = new Google_Service_MyBusiness_Category();
	$category->setCategoryId($value);	
	$additional_categories[] = $category ;
}

$websiteUrl = $loc["url"];

#Business hours 
$days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
$a_business_hours = [];
 foreach ($days as $day){
        $abbr = strtolower(substr($day,0,3));
        if( (($loc["$abbr_open"] == "05:00") && ($loc["$abbr_close"] == "05:00") ) || ($loc["$abbr_opt"] == "C"))	{
        	continue;
        }
        $business_Day = new Google_Service_MyBusiness_TimePeriod();
		$business_Day->setOpenDay(strtoupper($day));	
		$business_Day->setOpenTime($loc[$abbr."_open"]);
		$business_Day->setCloseDay(strtoupper($day));
		$business_Day->setCloseTime($loc[$abbr."_close"]);
		$a_business_hours[] = $business_Day ;
}
$listing = $postData["client"];
$business_hours = new Google_Service_MyBusiness_BusinessHours();
$business_hours->setPeriods($a_business_hours);


if( ($loc['longitude'] ==  "0.000000") || ( $loc['latitude'] ==  "0.000000") ){
	
	$fullAddress = $loc["address"].' '.$loc["address2"].' '.$city.' '.$state.' '.$zip; // Google HQ
	$prepAddr = str_replace(' ','+',$fullAddress);
	$urlmaps='https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $urlmaps);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	curl_close($ch);
	$response_a = json_decode($response);
 	$loc['latitude']= $response_a->results[0]->geometry->location->lat;
	$loc['longitude'] = $response_a->results[0]->geometry->location->lng;

}
$displayLat =  $loc['latitude'];
$displayLng =  $loc['longitude'];

$latlng = new Google_Service_MyBusiness_LatLng();
$latlng->setLatitude($displayLat);
$latlng->setLongitude($displayLng);

$business_desc = $loc["business_desc"] ; 

$profile = new Google_Service_MyBusiness_Profile();
$profile->setDescription($business_desc);

$paramsReq =array("validateOnly"=>false,"requestId"=> uniqid() );

try
    { 
        #TEST LOCATION
        $new_location = new Google_Service_Mybusiness_Location(); 
        $new_location->setStoreCode($storeCode);
        $new_location->setLocationName($name);
       if($loc['phone'] != ""){
			$new_location->setPrimaryPhone($loc['phone']);
		}
        $new_location->setLanguageCode("en");
        $new_location->setAddress($postalAddress);
        $new_location->setLatlng($latlng);
        $new_location->setWebsiteUrl($websiteUrl);
        $new_location->setRegularHours($business_hours);
     	$new_location->setPrimaryCategory($primaryCategory);
     	$new_location->setProfile($profile);
     
 	  	if(!empty($additional_categories)){
    	 	$new_location->setAdditionalCategories($additional_categories);
     	}
     	$new_location_rsp = $mybusinessService->accounts_locations->create($accountId, $new_location, $paramsReq);
                
	}
	catch(Exception $e){
		
			$errors[] = " Something went wrong adding a new Location for ".ucfirst($listing).", try again please. Exception: ".$e->getMessage();
			notify($errors, $store_id, $listing);
			exit();
		}
	$locationId = $new_location_rsp->getName();
