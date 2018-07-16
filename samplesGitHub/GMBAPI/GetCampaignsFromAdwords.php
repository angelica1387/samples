<?php
require __DIR__ . '/../googleads_v201806/vendor/autoload.php';

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\v201806\cm\CampaignService;
use Google\AdsApi\AdWords\Query\v201806\ServiceQueryBuilder;
use Google\AdsApi\AdWords\v201806\cm\OrderBy;
use Google\AdsApi\AdWords\v201806\cm\Paging;
use Google\AdsApi\AdWords\v201806\cm\Selector;
use Google\AdsApi\AdWords\v201806\cm\Predicate;
use Google\AdsApi\AdWords\v201806\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201806\cm\SortOrder;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Common\SoapSettingsBuilder;


/**
 * This example gets all campaigns. To add a campaign, run AddCampaign.php.
 */
class GetCampaigns
{

    const PAGE_LIMIT = 500;
    public static function pullCampaigns(
        AdWordsServices $adWordsServices,
        AdWordsSession $session, $filters = [], $startIndex = 0,$pageLimit=25
    ) {
		$campaigns_array = [];

        $campaignService = $adWordsServices->get($session, CampaignService::class);
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name', 'Status', 'Labels', 'TrackingUrlTemplate', 'ServingStatus','StartDate','EndDate','Settings','AdvertisingChannelType']);
        $predicates[] =   new Predicate('Status', PredicateOperator::NOT_IN, ['REMOVED']);
        $filters = ["name"=>"10000", /*"startdate"=>"2017-01-01", "enddate"=>"2037-12-30"*/];
        if(count($filters)){
            //Campaign Name Filter
            if(isset($filters["name"])){
                    $predicates[] =   new Predicate('Name', PredicateOperator::CONTAINS, ['0004-'.$filters["name"]]);
            }
            //Date Filter
           if(isset($filters["startdate"]) && isset($filters["enddate"]) ){
                $selector->setDateRange(new DateRange(date("Ymd",strtotime($filters["startdate"])),date("Ymd",strtotime($filters["enddate"]))));   
                $predicates[] =   new Predicate('StartDate', PredicateOperator::GREATER_THAN_EQUALS, [date("Ymd",strtotime($filters["startdate"]) )]);
    
            }
            else if(isset($filters["startdate"]) && !isset($filters["enddate"] )){
            $predicates[] =   new Predicate('StartDate', PredicateOperator::GREATER_THAN_EQUALS, [date("Ymd",strtotime($filters["startdate"]) )]);
            }
           
            if(isset($filters["search"])){
                $predicates[] =   new Predicate('Name', PredicateOperator::CONTAINS, [$filters["search"]]);
                 $predicates[] =   new Predicate('Labels', PredicateOperator::CONTAINS, [$filters["search"]]);
            }

        }

        $selector->setPredicates( $predicates);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
        $selector->setPaging(new Paging($startIndex,$pageLimit));

        // Create an AWQL query.
        $query = (new ServiceQueryBuilder())->select(['Id', 'Name', 'Status', 'Labels', 'TrackingUrlTemplate', 'ServingStatus','StartDate','EndDate','Settings','AdvertisingChannelType'])
            ->where('AdGroupId')
            ->equalTo($adGroupId)
            ->where('CombinedApprovalStatus')
            ->equalTo(PolicyApprovalStatus::DISAPPROVED)
            ->orderByAsc('Id')
            ->limit(0, self::PAGE_LIMIT)
            ->build();

  
        $totalNumEntries = 0;
        do {
            // Make the get request.
            try{
                $page = $campaignService->get($selector);
            }catch (Exception $ex){

                print_r($ex->getMessage()); exit;
            }
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                $campaigns_array = array_merge($campaigns_array,$page->getEntries());                    
            }
            // Advance the paging index.
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + $pageLimit
            );
        } while (($selector->getPaging()->getStartIndex() < $totalNumEntries ) && (count($campaigns_array) < $pageLimit)) ;
               
        return ["results"=>$campaigns_array, "totalResults"=>$totalNumEntries];
    }

	 public static function pullCampaignsAWQL(
        AdWordsServices $adWordsServices,
        AdWordsSession $session,
		$filters = [],
		$startIndex = 0,
		$pageLimit=25
    ) {
        $campaigns_array = [];
        $campaignService = $adWordsServices->get($session, CampaignService::class);
		$filters = [];
		$query = (new ServiceQueryBuilder())->select(['Id', 'Name', 'Status', 'Labels', 'TrackingUrlTemplate', 'ServingStatus','StartDate','EndDate','Amount','BudgetId','AdvertisingChannelType'])
					->where("Status")
					->notIn(['REMOVED']);
		if(count($filters)){
            //Campaign Name Filter
            if(isset($filters["name"])){
				$query  = $query->where('Name')
								->contains('0004-'.$filters["name"]);
            }
			if(isset($filters["startdate"])  ){
				$query  = $query->where('StartDate')
								->greaterThanOrEqualTo(date("Ymd",strtotime($filters["startdate"])));
            }
			if(isset($filters["enddate"])  ){
				$query  = $query->where('EndDate')
								->lessThanOrEqualTo(date("Ymd",strtotime($filters["enddate"])));
            }
			if(isset($filters["search"])){
				$query  = $query->where('Name')
								->contains($filters["search"])
								->where('Labels')
								->contains($filters["search"]);
            }
           
		}
			
        $query= $query				
				->orderByAsc('Name')
				->limit($startIndex,$pageLimit)
				->build();
				 
         do {
            // Advance the paging offset in subsequent iterations only.
            if (isset($page)) {
                $query->nextPage();
            }
            // Make the query request.
            $page = $campaignService->query(sprintf('%s', $query));
			
		
			$totalNumEntries = 	$page->getTotalNumEntries();
			
            if ($page->getEntries() !== null) {
				$campaigns_array = array_merge($campaigns_array, $page->getEntries());
            }
          } while ($query->hasNext($page) && count($campaigns_array)< $pageLimit);
			/*echo "<pre>";
			print_r($campaigns_array);
			echo "</pre>";*/
      
        return ["results"=>$campaigns_array, "totalResults"=>$totalNumEntries];
    }

	public static function main($clientId)
    {
		$config = __DIR__."/../googleads-php-lib/config/adsapi_php.ini";
		// Generate a refreshable OAuth2 credential for authentication.
		$oAuth2Credential = (new OAuth2TokenBuilder())
									->fromFile($config)
									->build();
        $soapSettings  = (new SoapSettingsBuilder())
                      ->disableSslVerify()
                      ->build(); 
		$session = (new AdWordsSessionBuilder())
            ->fromFile($config)
            ->withOAuth2Credential($oAuth2Credential)
            ->withSoapSettings($soapSettings)
            ->withClientCustomerId($clientId)
            ->build();	
             
        return self::pullCampaignsAWQL(new AdWordsServices(), $session);
    }
}

