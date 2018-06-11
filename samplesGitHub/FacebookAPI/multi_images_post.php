<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=May 18, 2017 
 * 
 */
 /*upload a multi story photo

  * Require Facebook Graph PHP SDK (Graph Api v2.9)
  * 
  *   */

$endpoint = "/".$page_id."/photos";

foreach ($multiple_photos as $file_url):
array_push($photos, $fb->request('POST',$endpoint,['url' =>$file_url,'published' => FALSE,]));
endforeach;

$uploaded_photos = $fb->sendBatchRequest($photos,  $page_access_token); 

foreach ($uploaded_photos as $photo):
array_push($data_post['attached_media'], '{"media_fbid":"'.$photo->getDecodedBody()['id'].'"}');
endforeach;

$data_post['message'] = $linkData['caption'];

$data_post['published'] = FALSE;

$data_post['scheduled_publish_time'] = $scheduled_publish_time;

$response = $fb->sendRequest('POST', "/".$page_id."/feed", $data_post, $page_access_token);

$post_id = $cresponse->getGraphNode()['id'];