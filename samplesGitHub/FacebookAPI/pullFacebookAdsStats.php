<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Jul 11, 2017
 * 
 */
//Pull Stats from all Ads Accounts under same Business Mananger User - Ad Level Data.
//Require access to Facebook Marketing API (Standard Access)

require  __DIR__ ."/includes/FacebookAds/vendor/autoload.php";


use FacebookAds\Api;
use FacebookAds\Object\Values\AdDatePresetValues;
/*** Ad Accounts ****/
use FacebookAds\Object\Fields\AdAccountFields;
/** Insights ***/
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Values\AdsInsightsActionBreakdownsValues;
use FacebookAds\Object\Values\AdsInsightsLevelValues;

/***Adset***/
use FacebookAds\Object\AdSet;

/****AdCreative****/
use FacebookAds\Object\Fields\AdCreativeFields;

const api_key = 'API_KEY';
const secret_token = 'SECRET_TOKEN';
const business_id = 'BUSINESS_USER_ID';
const extended_token = 'EXTENDED_TOKEN';

class FbStats{
	public static function pullStats($ad_account,&$insert_values, $s_date, $e_date ){
             
            $fields_1 = array(AdsInsightsFields::ACCOUNT_NAME,
                        AdsInsightsFields::ACCOUNT_ID,
                        AdsInsightsFields::CAMPAIGN_ID,
                        AdsInsightsFields::CAMPAIGN_NAME,
                        AdsInsightsFields::ADSET_ID,
                        AdsInsightsFields::ADSET_NAME,
                        AdsInsightsFields::DATE_START,
                        AdsInsightsFields::OBJECTIVE,
                        AdsInsightsFields::DATE_STOP,
                        AdsInsightsFields::REACH, 
                        AdsInsightsFields::IMPRESSIONS,
                        AdsInsightsFields::SPEND,
                        AdsInsightsFields::FREQUENCY,                       
                        AdsInsightsFields::CALL_TO_ACTION_CLICKS,
                        AdsInsightsFields::ACTIONS,
                        AdsInsightsFields::TOTAL_ACTIONS,
                        AdsInsightsFields::TOTAL_UNIQUE_ACTIONS,
                        AdsInsightsFields::UNIQUE_ACTIONS,
						AdsInsightsFields::CLICKS,                       
                        AdsInsightsFields::INLINE_LINK_CLICKS,
                        AdsInsightsFields::ESTIMATED_AD_RECALLERS 
					);
            
             $fields_2 = array(AdsInsightsFields::VIDEO_AVG_PERCENT_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_AVG_TIME_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_10_SEC_WATCHED_ACTIONS,
                       // AdsInsightsFields::VIDEO_15_SEC_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_30_SEC_WATCHED_ACTIONS,            
                        AdsInsightsFields::VIDEO_P25_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_P50_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_P75_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_P95_WATCHED_ACTIONS,
                        AdsInsightsFields::VIDEO_P100_WATCHED_ACTIONS,
						AdsInsightsFields::CTR,
					);
           
            $params_c['action_attribution_windows'] = array('1d_view', '28d_click');
            $params_c['action_breakdowns'] = [AdsInsightsActionBreakdownsValues::ACTION_LINK_CLICK_DESTINATION];
            $params_c['action_report_time'] = "impression";
            $params_c['level'] = AdsInsightsLevelValues::ADSET;
            /*Each Day Between a Range Date Given*/
            if(!is_null($s_date) && !is_null($e_date)){

                $start_date = new DateTime($s_date);
                $end_date = new DateTime($e_date);  

                    if($start_date <= $end_date){
                        $date_range = array();
                        while ( $start_date <= $end_date) {                           
                            array_push($date_range,array('since'=>$start_date->format('Y-m-d'),'until'=>$start_date->format('Y-m-d')));
                            $start_date->add(new DateInterval("P1D"));
                         } 
                         $params_c['time_ranges']=$date_range;
                    }        
           
            }else {
                $params_c['date_preset'] = AdDatePresetValues::YESTERDAY;
            }
           
            try{
               $adset_insights_1 = $ad_account->getInsights($fields_1,$params_c); 
                
            }catch (FacebookAds\Exception\Exception $ex) {
                print_r($ex->getMessage());
                exit();
            }             
           $adsets = [];
            if(isset($adset_insights_1) && ($adset_insights_1->count() > 0)){
              
                    do{
                        $adset_insights_1->fetchAfter();
                    } while ($adset_insights_1->getNext());

                    $adsets = $adset_insights_1->getArrayCopy(true);   
            }   
		
            if(!count($adsets)){
                return [];
            }			
                foreach ($adsets as $stats):    
                    /*Ad from an existing post*/
                    if($stats->__get('objective') == 'POST_ENGAGEMENT'){
                      
                        $adset_id = $stats->__get('adset_id');
                        try{
                            $adset = new AdSet($adset_id,$ad_account->__get('account_id') );
                            $object_story_id = $adset->getAdCreatives(array(AdCreativeFields::OBJECT_STORY_ID)); 
                        }
                        catch (FacebookAds\Exception\Exception $ex) {
                            return  $ex->getMessage().PHP_EOL;
                        }                       
                        $post_id = $object_story_id->current()->__get('object_story_id');      
                        if(empty($post_id) )
                             $post_id = $stats->__get('campaign_id');
                    }
                    else
                        $post_id = $stats->__get('campaign_id');
                    
                   $account_name = $stats->__get('account_name');                  
                   $campaing_name = $stats->__get('campaign_name'); 
                    $client = array();
                    self::getClientCampId($stats->__get('account_name'),$client,$campaing_name);                        
                
                          
                    $actions = (is_null($stats->__get('actions')))
                            ?[]:self::getActions($stats->__get('actions')); 

                $action_details = $stats->__get('actions');

            switch($stats->__get('objective')){
		#EVENT_RESPONSES
		case "EVENT_RESPONSES":
				$results = array_filter($action_details, function ($var) {
								return ($var->action_type == 'rsvp');
							});
			break;
		case "VIDEO_VIEWS" : 
				$results = array_filter($action_details, function ($var) {
								return ($var->action_type == 'video_view');
							});
			break;
		case "CONVERSIONS":
				$results = array_filter($action_details, function ($var) {
								return ($var->action_type == 'offsite_conversion.fb_pixel_lead');
							});
			break;
		case "POST_ENGAGEMENT" : 
				$results = $actions['engagement'][0]["value"];
			break;
		case "LEAD_GENERATION":
                                $results= array_filter($action_details, function ($var) {
							return ($var->action_type == 'leadgen.other');
						});
			break;
		case "LOCAL_AWARENESS":
				$results = $stats->__get('impressions');
			break;
		case "REACH":
				$results =$stats->__get('reach');
			break;
		case "PAGE_LIKES":
			$results = $actions['likes'][0]["value"];
			break;
		case "LINK_CLICKS":
			$results = $actions['link_clicks'];
			break;
		case "BRAND_AWARENESS":
			$results =$stats->__get('estimated_ad_recallers');
			break;			
		default :
			$results = 0;

	}

        $total_results =  0;

	if(is_array($results)){
		$values = array_values($results);
		foreach($values as $q){
			$total_results += $q->value;

		}
	}
	else{
		$total_results = $results;
        }
        
        
        $likes =  (!isset($actions['likes'][0]["value"]) || 
                                    ($actions['likes'][0]["value"] == ''))
                                    ?0:$actions['likes'][0]["value"];
        $link_clicks =  (!isset($actions['link_clicks']) || 
                                    ($actions['link_clicks'] == ''))
                                    ?0:$actions['link_clicks'];
        $postshares =  (!isset($actions['postshares'][0]["value"]) || 
                                    ($actions['postshares'][0]["value"] == ''))
                            ?0:$actions['postshares'][0]["value"];
        $engagements =  (!isset($actions['engagement'][0]["value"]) || 
                                    ($actions['engagement'][0]["value"] == ''))
                            ?0:$actions['engagement'][0]["value"];
        $unique_inline_link_clicks = (is_null( $stats->__get('unique_inline_link_clicks')) || 
                                    ( $stats->__get('unique_inline_link_clicks') == ''))
                            ?0: $stats->__get('unique_inline_link_clicks');
        $clicks = (is_null( $stats->__get('clicks')) || 
                                    ( $stats->__get('clicks') == ''))
                            ?0: $stats->__get('clicks');
        $imps= (is_null($stats->__get('impressions')) || 
                                    ($stats->__get('impressions') ==''))
                            ?0: $stats->__get('impressions');
        $reach = (is_null($stats->__get('reach')) || 
                                    ($stats->__get('reach') == ''))
                            ?0:$stats->__get('reach');
        $total_actions_value = (is_null($stats->__get('total_actions')) || 
                                    ($stats->__get('total_actions') == ''))
                            ?0:$stats->__get('total_actions');
        $frecuency = (is_null($stats->__get('frequency')) || 
                                    ($stats->__get('frequency') == ''))
                            ?0:number_format($stats->__get('frequency'),4);
       // print_r($stats->__get('unique_inline_link_clicks'));
                
        $video_10_sec_watched_actions = (is_null($stats->__get('video_10_sec_watched_actions')) || 
                                    ($stats->__get('video_10_sec_watched_actions') == ''))
                            ?0:$stats->__get('video_10_sec_watched_actions')[0]["value"];
       
        $video_15_sec_watched_actions = 0;
        $video_30_sec_watched_actions = (is_null($stats->__get('video_30_sec_watched_actions')) || 
                                    ($stats->__get('video_30_sec_watched_actions') == ''))
                            ?0:$stats->__get('video_30_sec_watched_actions')[0]["value"];
        $video_p25_watched_actions = (is_null($stats->__get('video_p25_watched_actions')) || 
                                    ($stats->__get('video_p25_watched_actions') == ''))
                            ?0:$stats->__get('video_p25_watched_actions')[0]["value"];
        $video_p50_watched_actions = (is_null($stats->__get('video_p50_watched_actions')) || 
                                    ($stats->__get('video_p50_watched_actions') == ''))
                            ?0:$stats->__get('video_p50_watched_actions')[0]["value"];
        $video_p75_watched_actions = (is_null($stats->__get('video_p75_watched_actions')) || 
                                    ($stats->__get('video_p75_watched_actions') == ''))
                            ?0:$stats->__get('video_p75_watched_actions')[0]["value"];
        $video_p95_watched_actions = (is_null($stats->__get('video_p95_watched_actions')) || 
                                    ($stats->__get('video_p95_watched_actions') == ''))
                            ?0:$stats->__get('video_p95_watched_actions')[0]["value"];
        $video_p100_watched_actions = (is_null($stats->__get('video_p100_watched_actions')) || 
                                    ($stats->__get('video_p100_watched_actions') == ''))
                            ?0:$stats->__get('video_p100_watched_actions')[0]["value"];
        $video_avg_percent_watched_actions = (is_null($stats->__get('video_avg_percent_watched_actions')) || 
                                    ($stats->__get('video_avg_percent_watched_actions') == ''))
                            ?0:$stats->__get('video_avg_percent_watched_actions')[0]["value"];
        $video_avg_time_watched_actions = (is_null($stats->__get('video_avg_time_watched_actions')) || 
                                    ($stats->__get('video_avg_time_watched_actions') == ''))
                            ?0:$stats->__get('video_avg_time_watched_actions')[0]["value"];
        $actions_text = is_null($stats->__get('actions'))?'':json_encode($stats->__get('actions'));
        $total_unique_actions = (is_null( $stats->__get('total_unique_actions')) || 
                                    ( $stats->__get('total_unique_actions') == ''))
                            ?0: $stats->__get('total_unique_actions');
        
        $total_results = ($total_results == '')?0:$total_results;
      
                            $lines  = "('".
/*client*/                              $client['client_id']."',".
/*campid*/                             $client['campid'].",".
/*date*/                            "'". $stats->__get('date_start')."',".
/*likes*/                            $likes.",".
/*reach*/                            $reach.",". 
/*spend*/                            number_format($stats->__get('spend'),2).",". 
/*clicks*/                           $clicks.",". 
/*link_clicks*/                      $link_clicks.",". 
/*imps*/                            $imps.",". 
/*engagement*/                      $engagements.",". 
/*results*/                         $total_results.",". 
/*result_types*/                     "'".$stats->__get('objective')."',". 
/*adsetname*/                        '"'.str_replace('"', '\"',$stats->__get('adset_name')).'",'.   
/*postshares*/                       $postshares.",". 
/*actions*/                          $total_actions_value.",". 
/*actions_details*/                  "'".$actions_text."',". 
/*campaignid*/                       "'".$stats->__get('campaign_id')."',".
/*campaignname*/                     '"'.str_replace('"', '\"',$stats->__get('campaign_name')).'",'. 
/*date_stop*/                        "'".$stats->__get('date_stop')."',".
/*total_unique_actions*/             $total_unique_actions.",".
/*frequency*/                        $frecuency.",".
/*inline_link_clicks*/               $unique_inline_link_clicks.",".
/*video_10_sec_watched*/             $video_10_sec_watched_actions.",".
/*video_15_sec_watched*/             $video_15_sec_watched_actions.",".
/*video_30_sec_watched*/             $video_30_sec_watched_actions.",".
/*video_p25_watched*/                $video_p25_watched_actions.",".
/*video_p50_watched*/                $video_p50_watched_actions.",".
/*video_p75_watched*/                $video_p75_watched_actions.",".
/*video_p95_watched*/                $video_p95_watched_actions.",".
/*video_p100_watched*/               $video_p100_watched_actions.",".
/*video_avg_percent_watched*/        $video_avg_percent_watched_actions.",".
/*video_avg_time_watched*/           $video_avg_time_watched_actions.",".
/*post_id*/                        "'".$post_id."')";                 
/*post_id*/                        "'".$post_id."')";                 
                 $insert_values[] =  $lines;
                endforeach; 
            //} #end IF 
          
	}
        
         private static function getClientCampId($account_name, &$client_camp_id, $campaign_name = null ){                       
            
                 if(preg_match('/\(([\d]+\-?[\d]+)\)/',$campaign_name, $out) == 1){
                 $client_camp_id['client_id']= $out[1];
                }
                else{
                     $client_camp_id['client_id'] = 0;  
                }
            
            if(preg_match('/\[[\d]+]/', $account_name, $out) == 1){
              
                $client_camp_id['campid'] = ltrim($out[0],'[');
                $client_camp_id['campid'] = rtrim($client_camp_id['campid'], ']');             
            } 
            else if((preg_match('/\[[\d]+]/', $campaign_name, $out) == 1) && !isset($client_camp_id['campid'])){
                
                $client_camp_id['campid'] = ltrim($out[0],'[');
                $client_camp_id['campid'] = rtrim($client_camp_id['campid'], ']');  
            }else
                $client_camp_id['campid'] = 0;                
       }
    
        private static function getActions($all_actions ){
            
            //print_r($all_actions);
            $actions = [];

            $actions["click_to_website"] = array_filter($all_actions, function ($var) {
                    return (isset($var['action_link_click_destination']) && ($var['action_link_click_destination'] == 'click_to_website'));
                    });
            $actions["click_to_website"]  =  array_values($actions["click_to_website"]);
            $actions["likes"] = array_filter($all_actions, function ($var) {
                    return ($var['action_type'] == 'like');
                    });
            $actions["likes"]  =  array_values($actions["likes"]);
            $actions["postshares"] = array_filter($all_actions, function ($var) {
                            return ($var['action_type'] == 'post');
                    });
            $actions["postshares"]  =  array_values($actions["postshares"]);
            $actions["engagement"] = array_filter($all_actions, function ($var) {
                    return ($var['action_type'] == 'post_engagement');
                    });
                    
            $actions["engagement"]  =  isset($actions["engagement"])?array_values($actions["engagement"]):[];

            $actions_link_click = array_filter($all_actions, function ($var) {
                                    
                                    return ($var['action_type'] == 'link_click');
                                });							
            $link_click  =  array_values($actions_link_click);

            $total_link_clicks = 0;

            foreach($link_click as $q){
                    $total_link_clicks += $q["value"];
            }
            $actions["link_clicks"] = $total_link_clicks;
            return $actions;
            
        }
 	public static function main($businessObject, $db, $start_date, $end_date) {
            $user = $businessObject;
           
            try{
            //get all ad_account from User Business
            
			
            $business_accounts[] = $user->read()->getClientAdAccounts(
                          array(
                        AdAccountFields::ID,
                        AdAccountFields::NAME,
                        AdAccountFields::ACCOUNT_STATUS,
                    
                     ),  array('limit'=>1000,)
                    );
                $business_accounts[]	= $user->read()->getOwnedAdAccounts(
                  array(
                AdAccountFields::ID,
                AdAccountFields::NAME,
                AdAccountFields::ACCOUNT_STATUS,

             ),  array('limit'=>1000,)
            );			
        } catch (FacebookAds\Exception\Exception $ex) {
            $error = $ex->getMessage();
            print_r($error);
        }
        if(isset($business_accounts) && (count($business_accounts) > 0)){
			  
        $insert_values = [];
		foreach($business_accounts as $accounts ){
			do {					
                                $ad_account = $accounts->current();  
                                if($ad_account->__get("account_status")){ 
                                    self::pullStats($ad_account,$insert_values,$start_date, $end_date); 

                                    }
                                 $accounts->next();
					
			} while ($accounts->current());  
		}
   
        try{
            if(!mysqli_query($db, $insert_stmt)){
                 file_put_contents(__DIR__."/error_".date("Y-md-").".txt","[PULLING_FBADS_ERRORS][".date("Y-m-d H:i:s")."]". mysqli_error($db));  
            }
        } catch (Exception $ex) {
            file_put_contents(__DIR__."/error_".date("Y-m-d").".txt","[PULLING_FBADS_ERRORS][".date("Y-m-d H:i:s")."]".$ex->getMessage()); 
        }
      
        exit("runs");
        }
    }

}
error_reporting(E_ERROR);

$path_cert = __DIR__.'/includes/FacebookAds/vendor/facebook/php-ads-sdk/fb_ca_chain_bundle.crt';

Api::init(api_key,secret_token,extended_token)->getHttpClient()->setCaBundlePath( $path_cert);

$api = Api::instance();

$user = new \FacebookAds\Object\Business(business_id);
$db_link = mysqli_connect("host", "user", "pass", "db_name");

FbStats::main($user, $db_link, isset($argv[1])?$argv[1]:null,isset($argv[1])?$argv[2]:null );
