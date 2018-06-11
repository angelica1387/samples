<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=May 20, 2017
 * 
 */

/*
 * Create a new location for an existing page
 * Facebook SDK PHP - Facebook Graph API v2.9
 */

$fb = new Facebook(['app_id' => app_idp,'app_secret' => app_secret,]);
$fb->setDefaultAccessToken(master_page_access_token);
$new_location['hours'] = array("mon_1_open"=> "10:00",
        "mon_1_close"=>"19:00",
        "tue_1_open"=> "10:00",
        "tue_1_close"=> "19:00",
        "wed_1_open"=>"10:00",
        "wed_1_close"=> "19:00",
        "thu_1_open"=> "10:00",
        "thu_1_close"=> "19:00",
        "fri_1_open"=> "10:00",
        "fri_1_close"=> "19:00",
        "sat_1_open"=>"10:00",
        "sat_1_close"=>"19:00"
);
$location =  array(
        'hours'=>$new_location['hours'],
        'location'=> array(
        'street'=>$full_address,
        'latitude' => $latitude,
        'longitude'=> $longitude,
        'city_id'=> $city_id,
        'zip'=> $zip),
        'phone' => $phone ,
        'place_topics'=>array('Place_ID',
                'Place_ID',
                'Place_ID'),
        'price_range' => '$$',
        'store_location_descriptor' => $store_location_descriptor,
        'store_name'=> $store_name,
        'store_number'=> $store_id,
);
try {
        $new_pages =  $fb->post('/{page_id}/locations',$location,master_page_access_token); 
} 
catch (Exceptions\FacebookResponseException $exc) {
        echo $exc->getMessage();
}