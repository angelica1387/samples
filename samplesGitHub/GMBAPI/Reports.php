<?php

/*
 * 
 *@author=Angelica Espinosa <angelica@das-group.com>
 *@date=Sep 14, 2017 
 * 
 */
require __DIR__ . '/../vendor/autoload.php';

require __DIR__."/GoogleAds.php";
use Google\AdsApi\AdWords\Reporting\v201802\ReportDownloader;
use Google\AdsApi\AdWords\Reporting\v201802\DownloadFormat;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201802\cm\ApiException;


class GoogleAdsReport extends GoogleAds{
    public function __construct($config) {
        parent::__construct($config);
    }
   
    public function getClientPerformace($dateRange,
                            $reportFormat = DownloadFormat::XML){
        
        $reportQuery = 'SELECT ExternalCustomerId,Date,CustomerDescriptiveName,'
            . 'Clicks,Impressions,Engagements,Interactions,Cost,'
            . 'AverageCost,AverageCpc,AveragePosition,'
            . 'SearchImpressionShare,SearchBudgetLostImpressionShare,'
            . 'SearchRankLostImpressionShare,Conversions, ViewThroughConversions '
            . ' FROM ACCOUNT_PERFORMANCE_REPORT '
            . ' DURING '.$dateRange ;
        
        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        // request, you can create and supply the settings override here. Otherwise,
        // default values from the configuration file (adsapi_php.ini) are used.
        $reportSettingsOverride = (new ReportSettingsBuilder())
            ->skipColumnHeader(true)
            ->skipReportHeader(true)
            ->skipReportSummary(true)
            ->includeZeroImpressions(false)
            ->build();
        $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
            $reportQuery, $reportFormat, $reportSettingsOverride);
        //print "Report was downloaded and printed below:\n";
   
      ///  return $reportDownloadResult->getAsString();
        return $this->formatResult($reportDownloadResult->getAsString());
        
    }    
    public function getCampaignsPerformace($dateRange,
                            $reportFormat = DownloadFormat::CSV){
        //,ExternalConversionSource
        $reportQuery = 'SELECT CampaignId,Date,CampaignName,CampaignStatus,'
            . 'AdvertisingChannelType,Clicks,Impressions,'
            . 'Engagements,Interactions,Cost,Amount,AverageCost,AverageCpc,'
            . 'AverageFrequency,AveragePosition,'
            . 'ContentBudgetLostImpressionShare,ContentImpressionShare,ContentRankLostImpressionShare,'
            . 'SearchImpressionShare,'
            . 'SearchBudgetLostImpressionShare,SearchRankLostImpressionShare,'
            . ' Conversions,'
            . ' ViewThroughConversions, NumOfflineInteractions'
            . ' FROM CAMPAIGN_PERFORMANCE_REPORT '
            . ' WHERE CampaignStatus IN [ENABLED]'
            . ' DURING '.$dateRange ;
        
        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        // request, you can create and supply the settings override here. Otherwise,
        // default values from the configuration file (adsapi_php.ini) are used.
        $reportSettingsOverride = (new ReportSettingsBuilder())
            ->skipColumnHeader(true)
            ->skipReportHeader(true)
            ->skipReportSummary(true)
            ->includeZeroImpressions(true)
            ->build();
        $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
            $reportQuery, $reportFormat, $reportSettingsOverride);
        //print "Report was downloaded and printed below:\n";
   
        return  $this->formatResult($reportDownloadResult->getAsString());
        
    }   
   
    public function getKeywordPerformance2($dateRange,  $reportFormat = DownloadFormat::CSV){
       $campId= 304084134;    
      
       $reportQuery = 'SELECT Id,Criteria,AdGroupName,Status,KeywordMatchType,'
            . ' IsNegative,CampaignId,Date,QualityScore,PostClickQualityScore,CreativeQualityScore, '
            . ' TopOfPageCpc,FirstPageCpc,FirstPositionCpc, '
            . ' Clicks,Impressions,Engagements,Interactions,Cost,CpcBid, '
            . ' AverageCost,AverageCpc,AveragePosition, '
            . ' SearchExactMatchImpressionShare,SearchImpressionShare,SearchRankLostImpressionShare,'
            . ' Conversions,AllConversions,ViewThroughConversions, CampaignName '
            . ' FROM KEYWORDS_PERFORMANCE_REPORT '
            . ' WHERE IsNegative IN [true, false] and Status IN [ENABLED] AND CampaignId IN [119871654]'
            . ' DURING '.$dateRange ;  
    // Download report as a string.
    $reportDownloader = new ReportDownloader($this->session);
    // Optional: If you need to adjust report settings just for this one
    // request, you can create and supply the settings override here. Otherwise,
    // default values from the configuration file (adsapi_php.ini) are used.
    $reportSettingsOverride = (new ReportSettingsBuilder())
            ->skipColumnHeader(true)
            ->skipReportHeader(true)
            ->skipReportSummary(true)
            ->includeZeroImpressions(true)
            ->build();
    $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
        $reportQuery, $reportFormat, $reportSettingsOverride);
    //print "Report was downloaded and printed below:\n";
    
//    print_r($reportDownloadResult->getAsString());
//    exit();
    return $this->formatResult($reportDownloadResult->getAsString());
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
           
        $filePath = __DIR__."/../../../adwords_reports/"
                //"D:/Website/htdocs/_Test_Sites/site24/htdocs/adwords_reports/"
                . $reportName;
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
            return;
        }

        //if($this->downloadReport($reportQuery, $filePath)){
                print_r($this->downloadReport($reportQuery, $filePath, DownloadFormat::XML,false));
    //            printf("Report with name '%s' was downloaded.\n",
    //               $reportName, $filePath);
        //}

  }
    public function downloadCampaignsPerformace($client ,$rangeDate = null){ 
    
        if(is_null($rangeDate)){
         $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        
        $reportName = "CAMPAIGN_PERFORMANCE_REPORT_".$client."_".$rangeDate;
        //exit($reportName);
       
        $filePath = __DIR__."/../../../adwords_reports/"
                //"D:/Website/htdocs/_Test_Sites/site24/htdocs/adwords_reports/"
            . $reportName;
        
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
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
        
              print_r($this->downloadReport($reportQuery, $filePath,
                      DownloadFormat::XML,true));
  
  }
    public function downloadKeywordPerformance($client ,$rangeDate = null){ 
        
        if(is_null($rangeDate)){
            $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        $reportName = "KEYWORDS_PERFORMANCE_REPORT_".$client."_".$rangeDate;
        $filePath = __DIR__."/../../../adwords_reports/"
               // "D:/Website/htdocs/_Test_Sites/site24/htdocs/adwords_reports/"
            . $reportName;
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
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
                    //. "AND AdGroupStatus = ENABLED "
                   // . "AND CampaignStatus = ENABLED "
                    . "AND CampaignStatus IN [ENABLED,PAUSED] "
                    . "DURING ". $rangeDate;
        
        if($this->downloadReport($reportQuery, $filePath,DownloadFormat::XML,false)){
            printf("Report with name '%s' was downloaded.\n",
               $reportName, $filePath);
       }  
   
  }
    public function downloadGeoPerformance($client ,$rangeDate = null){ 
        
        if(is_null($rangeDate)){
             $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        $reportName = "CAMPAIGN_LOCATION_TARGET_REPORT_".$client."_".$rangeDate;
        $filePath = __DIR__."/../../../adwords_reports/"
                //"D:/Website/htdocs/_Test_Sites/site24/htdocs/adwords_reports/"
            . $reportName;
        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
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
          
            print_r("Report with name '%s' was downloaded.\n",
               $reportName, $filePath);
             
       }                

  }
  public function downloadSearchTermsPerformance($client ,$rangeDate = null){ 
        // print_r($this->session->getClientCustomerId()); exit;
        if(is_null($rangeDate)){
             $rangeDate =  date('Ymd',strtotime("-2 days")).",".date('Ymd',strtotime("-2 days"));
        }
        $reportName = "CAMPAIGN_SEARCH_TERMS_REPORT_".$client."_".$rangeDate;
        $filePath = __DIR__."/../../../adwords_reports/"
                //"D:/Website/htdocs/_Test_Sites/site24/htdocs/adwords_reports/"
            . $reportName;

        if(file_exists($filePath."." .strtolower( DownloadFormat::XML) )){
                printf("Report with name '%s' was previously downloaded.\n",
                   $reportName);
            return;
        }

        $reportQuery = "SELECT ".
                            "CampaignId,CampaignName,AdGroupId,KeywordId,DestinationUrl,".
                            "Query,QueryTargetingStatus,Device,".
                            "Date,Clicks,Impressions,Engagements,Cost,".
                            "Conversions,AllConversions,AveragePosition,".
                            "VideoViews,EngagementRate "    
                    ." FROM SEARCH_QUERY_PERFORMANCE_REPORT "
                    ." WHERE CampaignStatus IN [ENABLED,PAUSED] "
                    ." DURING ". $rangeDate;   
          //  print_r($filePath); exit;        
        if($this->downloadReport($reportQuery, $filePath,DownloadFormat::XML,false)){
          
            print_r("Report with name '%s' was downloaded.\n",
               $reportName, $filePath);
             
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
        catch (Exception $ex) {            
                print_r($ex->getErrors());
              exit("Exception");
        } catch (AdwordsApiException  $ex) {            
                print_r($ex->getErrors());
              exit("Exception");
        }
        catch (ApiException $ex) {            
                print_r($ex->getErrors());
            exit("ApiException");
        }

      return $reportDownloadResult->saveToFile($filePath."." .strtolower($downloadFormat));
  }
}
 
