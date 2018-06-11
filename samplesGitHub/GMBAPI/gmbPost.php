<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=May 8, 2018 
 * 
 */
/*
 * for brands that have more than ten locations are not allowed to post on GMB through the API. 
 * Pull the location and check this flag $location->getLocationState()->getIsLocalPostApiDisabled()
 */
$posts = $mybusinessService->accounts_locations_localPosts;


$newPost->setSummary("Post Message Here!!");           
$newPost->setLanguageCode("en-US");
$calltoaction = new Google_Service_MyBusiness_CallToAction();

$calltoaction->setActionType("ORDER");

$calltoaction->setUrl("http://google.com/order_turkeys_here");

$newPost->setCallToAction($calltoaction);

$media = new Google_Service_MyBusiness_MediaItem();

$media->setMediaFormat("PHOTO");
$media->setSourceUrl("https://www.google.com/real-turkey-photo.jpg");

$newPost->setMedia($media); 

$listPostsResponse = $posts->create( $location_name, $newPost);
