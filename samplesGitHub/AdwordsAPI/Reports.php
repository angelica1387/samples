<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Sep 14, 2017 
 * 
 */

include_once __DIR__."/../config/config.php";
require __DIR__ . '/../vendor/autoload.php';

require __DIR__."/GoogleAds.php";


use Google\AdsApi\AdWords\Reporting\v201802\ReportDownloader;
use Google\AdsApi\AdWords\Reporting\v201802\DownloadFormat;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201802\cm\ApiException;



class GoogleAdsReport extends GoogleAds{

    public function __construct($config) {
       
        parent::__construct($config);
        //global $REPORTS_PATH;
    }
    public function switchCostumerId($clientCostumerId){        
        
        $this->session = (new AdWordsSessionBuilder())
            ->fromFile($this->configFile)
            ->withOAuth2Credential($this->oAuth2Credential)
            ->withSoapSettings($this->soapSettings)
            ->withClientCustomerId($clientCostumerId)
            ->build();
    }
    public function downloadClientPerformace($client ,$rangeDate = null){ 

        if(is_null($rangeDate)){
            $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
    
        $reportName = "ACCOUNT_PERFORMANCE_REPORT_".$client."_".$rangeDate;
        $reportQuery = 'SELECT ExternalCustomerId,Date,CustomerDescriptiveName,'.
                        'Clicks,Impressions,Engagements,Interactions,Cost,AverageCost'.
                        ',AverageCpc,AveragePosition,SearchImpressionShare,'.
                        'SearchBudgetLostImpressionShare,SearchRankLostImpressionShare,'.
                        'Conversions, ViewThroughConversions '
                        .'FROM ACCOUNT_PERFORMANCE_REPORT '
                        .'DURING '. $rangeDate;
           
        $filePath = REPORTS_PATH . $reportName;
        
        if(file_exists($filePath.".xml" )){
            echo "<pre>";
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
            echo "</pre>";
            return;
        }
            echo "<pre>";
            print_r($this->downloadReport($reportQuery, $filePath, "XML", false)); 
            echo "</pre>";  

  }
    public function downloadCampaignsPerformace($client ,$rangeDate = null){ 
    
        if(is_null($rangeDate)){
         $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        
        $reportName = "CAMPAIGN_PERFORMANCE_REPORT_".$client."_".$rangeDate;
       
        $filePath = REPORTS_PATH . $reportName;
        
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
                echo "<pre>";
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
                echo "</pre>";
            return;
        }
      
        $reportQuery = "SELECT ".
                           "CampaignId,Date,CampaignName,CampaignStatus,".
                           "AdvertisingChannelType,Clicks,Impressions,Engagements,".
                           "Interactions,Cost,Amount,AverageCost,AverageCpc,".
                           "AverageFrequency,AveragePosition,ContentBudgetLostImpressionShare,".
                           "ContentImpressionShare,ContentRankLostImpressionShare,".
                           "SearchImpressionShare,SearchBudgetLostImpressionShare,".
                           "SearchRankLostImpressionShare,Conversions,ViewThroughConversions,".
                           "NumOfflineInteractions"
                    ." FROM CAMPAIGN_PERFORMANCE_REPORT "
                    ." WHERE CampaignStatus IN [ENABLED,PAUSED] "
                    ." DURING ". $rangeDate;
        
            echo "<pre>";
              print_r($this->downloadReport($reportQuery, $filePath,
                      DownloadFormat::XML,true));
            echo "</pre>"; 
  }
    public function downloadGeoPerformance($client ,$rangeDate = null){ 
        
        if(is_null($rangeDate)){
             $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        $reportName = "CAMPAIGN_LOCATION_TARGET_REPORT_".$client."_".$rangeDate;
        $filePath = REPORTS_PATH . $reportName;
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
            echo "<pre>";
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
            echo "</pre>";
            return;
        }
        $reportQuery = "SELECT ".
                            "Id,CampaignId,Date,BidModifier,".
                            "Clicks,Impressions,Engagements,Interactions,Cost,AverageCost,".
                            "AverageCpc,AveragePosition,Conversions,AllConversions,".
                            "ViewThroughConversions "    
                    ." FROM CAMPAIGN_LOCATION_TARGET_REPORT "
                    ." WHERE IsNegative IN [FALSE] "
                    . " AND CampaignStatus IN [ENABLED,PAUSED] "
                    ." DURING ". $rangeDate;                
        if($this->downloadReport($reportQuery, $filePath,DownloadFormat::XML,false)){
            echo "<pre>";
            print_r("Report with name '%s' was downloaded.\n",
               $reportName, $filePath);
            echo "</pre>";       
       }                

  }
    public function downloadKeywordPerformance($client ,$rangeDate = null){ 
        
        if(is_null($rangeDate)){
            $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        $reportName = "KEYWORDS_PERFORMANCE_REPORT_".$client."_".$rangeDate;
        $filePath =REPORTS_PATH. $reportName;
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
            echo "<pre>";
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
            echo "</pre>";
            return;
        }
         $reportQuery = "SELECT ".
                            "Id,Criteria,AdGroupName,Status,KeywordMatchType,".
                            "CampaignId,Date,QualityScore,PostClickQualityScore,CreativeQualityScore,".
                            "TopOfPageCpc,FirstPageCpc,FirstPositionCpc,".
                            "Clicks,Impressions,Engagements,Interactions,Cost,CpcBid,".
                            " AverageCost,AverageCpc,AveragePosition,SearchExactMatchImpressionShare,".
                            "SearchImpressionShare,SearchRankLostImpressionShare,".
                            "Conversions,AllConversions,ViewThroughConversions, CampaignName"
                    . " FROM KEYWORDS_PERFORMANCE_REPORT "
                    . " WHERE Status  IN [PAUSED,ENABLED] "
                    . "AND IsNegative IN [FALSE] "
                    . "AND CampaignStatus IN [ENABLED,PAUSED] "
                    . "DURING ". $rangeDate;
        
        if($this->downloadReport($reportQuery, $filePath,DownloadFormat::XML,false)){
            echo "<pre>";
            printf("Report with name '%s' was downloaded.\n",
               $reportName, $filePath);
            echo "</pre>";       
       }  
   
  }
    public function downloadReport($reportQuery, $filePath,$downloadFormat, $zeroImpressions) {

        $reportDownloader = new ReportDownloader($this->session);
        
        $reportSettingsOverride = (new ReportSettingsBuilder())
                ->skipColumnHeader(true)
                ->skipReportHeader(true)
                ->skipReportSummary(true)
                ->includeZeroImpressions($zeroImpressions)
                ->build();
       try {            
            $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
                $reportQuery, $downloadFormat, $reportSettingsOverride);  
  
        } 
        catch (ApiException $ex) {            
            echo "<pre>";
                print_r($ex->getErrors());
            echo "</pre>"; 
            exit();
        }

      return $reportDownloadResult->saveToFile($filePath."." .strtolower($downloadFormat));
  }
}
 