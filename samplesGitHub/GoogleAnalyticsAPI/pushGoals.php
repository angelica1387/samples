<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Mar 14, 2018 
 * 
 */
/*
 * Analytics Management API v3
 * Creating a new goal 
 * (PHP Google_Client, Google_Service_Analytics)
 */
/*
    Assuming there is a $goals array
*/

$client = new Google_Client();
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->addScope([Google_Service_Analytics::ANALYTICS_READONLY,
                    Google_Service_Analytics::ANALYTICS_EDIT]);

$client->setDeveloperKey($API_KEY);
$client->setSubject($EMAIL);  
$client->refreshToken($REFRESH_TOKEN);

$client->setUseBatch(true);
$analytics = new Google_Service_Analytics($client);     
$batch = $analytics->createBatch();

foreach($goals as $goal){

    $req1 = $analytics->management_goals->insert('XXXXXX', 'UA-XXXXXX-1',' XXXXXX', $goal);
    $batch->add($req1);
}

try {
        $batchResponse = $batch->execute();
        /* Handling Response */
        foreach ($batchResponse as $key => $value) {

            if(!($value instanceof Google_Service_Exception)){
                echo $value->getId()."\n";
                continue;
            }
            print_r($value->getErrors());

        }
} catch (Google_Service_Exception $e) {
    /*
        handling exception  
    */
}