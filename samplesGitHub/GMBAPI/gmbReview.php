<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Feb 22, 2018 
 * 
 */
/*
 * /*$accounts previusly populate*/
/*(GMB - v4)*/
$credentials_f = "google_my_business_credentials_file.json";
$client = new Google_Client();
$client->setApplicationName($aplicattion_name);
$client->setDeveloperKey($developer_key);
$client->setAuthConfig($credentials_f);  
$client->setScopes("https://www.googleapis.com/auth/plus.business.manage");
$client->setSubject($accounts->email);   
$token = $client->refreshToken($accounts->refresh_token);
$client->authorize();

$locationName = "accounts/#######/locations/########";

$mybusinessService = new Google_Service_Mybusiness($client);

$reviews = $mybusinessService->accounts_locations_reviews;

do{
    $listReviewsResponse = $reviews->listAccountsLocationsReviews($locationName, array('pageSize' => 100,
                        'pageToken' => $listReviewsResponse->nextPageToken));

    $reviewsList = $listReviewsResponse->getReviews();
    foreach ($reviewsList as $index => $review) {
        /*Accesing $review Object

        * $review->createTime;
        * $review->updateTime;
        * $review->starRating;
        * $review->reviewer->displayName;
        * $review->reviewReply->comment;
        * $review->getReviewReply()->getComment();
        * $review->getReviewReply()->getUpdateTime();
        */

    }

}while($listReviewsResponse->nextPageToken);

