<?php	
	/*
	*Steps
	*Create Subscription
	*Save subscription_id 
	*Add new row to adcost table
	*Update Adwords Budget
	*/
	include __DIR__ .'/../../connect.php';
	include_once __DIR__ .'/../../functions.php';
	require __DIR__ . '/../../googleads_v201806/vendor/autoload.php';
	include __DIR__ . '/../../googleads_v201806/config/config.php';
	//Adwords API 
	use Google\AdsApi\AdWords\AdWordsServices;
	use Google\AdsApi\AdWords\AdWordsSession;
	use Google\AdsApi\AdWords\AdWordsSessionBuilder;
	use Google\AdsApi\AdWords\v201806\cm\Campaign;
	use Google\AdsApi\AdWords\v201806\cm\CampaignOperation;
	use Google\AdsApi\AdWords\v201806\cm\CampaignService;
	use Google\AdsApi\AdWords\v201806\cm\CampaignStatus;
	use Google\AdsApi\AdWords\v201806\cm\Operator;
	use Google\AdsApi\Common\OAuth2TokenBuilder;
	use Google\AdsApi\Common\SoapSettingsBuilder;
	#Budget
	use Google\AdsApi\AdWords\v201806\cm\BudgetService;
	use Google\AdsApi\AdWords\v201806\cm\BudgetOperation;
	use Google\AdsApi\AdWords\v201806\cm\BudgetBudgetDeliveryMethod;
	use Google\AdsApi\AdWords\v201806\cm\Budget;
	use Google\AdsApi\AdWords\v201806\cm\Money;
	#QueryBuilder
	use Google\AdsApi\AdWords\Query\v201806\ServiceQueryBuilder;
	//Monolog Logger 
	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;
	
	//Stripe Set Up
	$request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_STRING);	
	$storeNotificationsEmail = "angelica@das-group.com";	
	$customer_id = filter_var($_POST['customer_id'], FILTER_SANITIZE_STRING);
	
	$path_logs = __DIR__."/logs";

	if(!file_exists($path_logs)){	
		$result = mkdir($path_logs, "0777");
	}
	$logfile = "$path_logs/".date("Y-m-d").".log";
	
	$log = new Logger('Google Adwords');
	$log->pushHandler(new StreamHandler($logfile, Logger::INFO));
	$log->addInfo("****** START EXECUTION **********");
		
	$stmt = $conn->prepare("SELECT i.campaignId, i.store_id, i.client,".
									"i.start_date,i.new_daily_budget,".
									"i.amount,i.card_id , i.campaign_name ".
							"FROM advtrack.adwords_budget_increase i ".
							"WHERE i.id = ? AND i.status = '1'");
	
	$stmt->bind_param('i', $request_id);
	$stmt->execute();
	
	$data = $stmt->get_result()->fetch_assoc();
	if(!count($data))	
		exit;
	$rlocationData = $conn->query("SELECT * FROM locationlist where storeid='".$data["store_id"]."'");
	$locationData = $rlocationData->fetch_assoc();
//Notifications
	$subject = "DAS Google Adwords ";
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	$headers .= 'From: <'.strtolower($client).'@das-group.com>' . "\r\n";
	$userNotifications = "angelica@das-group";
	
	$log->addInfo("StoreId: ".$data["store_id"],$data);
	
	$r_adwords_setup = $conn->query("SELECT * FROM advtrack.adwords_setup ".
								" WHERE client='".$data["client"]."-".$data["store_id"]."'".
								"UNION ".
								"SELECT * FROM advtrack.adwords_setup WHERE client='".$data["client"]."'".
								" LIMIT 1");
								
	$adwords_setup  = $r_adwords_setup->num_rows ? $r_adwords_setup->fetch_assoc() : [];
	
	$r_adwords_markup = $conn->query("SELECT * FROM advtrack.adwords_markup ".
								" WHERE client='".$data["client"]."-".$data["store_id"]."'".
								"UNION ".
								"SELECT * FROM advtrack.adwords_markup WHERE client='".$data["client"]."'".
								" LIMIT 1");
	$adwords_markup  = $r_adwords_markup->num_rows ? $r_adwords_markup->fetch_assoc() : [];
	
	if(!count($adwords_markup) || !count($adwords_setup)){
		$log->addError("Client hasn't been setup, please setup before continue.",$data );
		$log->addInfo("****** END EXECUTION **********");
		exit;		
	}
//Look up customer from Stripe
	$log->addInfo("Retrieving Customer Info :$customer_id" );
	$customer =  \Stripe\Customer::retrieve($customer_id);
	$default_card = $data["card_id"];
	$card = $customer->sources->retrieve($data["card_id"]);	
	$last_digits = $card->object == "source" ? $card->card->last4 : $card->last4  ;
	$card_brand = $card->object == "source" ? $card->card->brand : $card->brand  ;
	$data["card_info"] = $card_brand . " Ending in " . $last_digits;
	
	//stripe fees 2.9% + 30¢ 
	$data["start_date"] = date("Y-m-d");
	$subscription_array = array(
							  "customer" => $customer_id,
							  "items" => array(
										array(
										  "plan" => $STRIPEPLAN,
										  "quantity" => $data["amount"],
										),
									  ),
							   "source" => $default_card
							);  
				
	if(date("d", strtotime($data["start_date"])) != $first_cycle_day ){
		
		$next_cycle = date("d", strtotime($data["start_date"])) >= $first_cycle_day 
					? (new DateTime($data["start_date"]))->modify('first day of next month')->format("Y-m-$first_cycle_day") 
					: date("Y-m-$first_cycle_day");
		$days_for_next_billing = (new DateTime($next_cycle))->diff(new DateTime($data["start_date"])); 			
		$prorrated_amount_billed = (($data["amount"] / $days_for_next_billing->d)*1.029)+ (0.30);
		$subscription_array = array_merge($subscription_array, ["billing_cycle_anchor"=>strtotime("$next_cycle 04:00:01"),
																"prorate" => true,
																]);
	}
	$log->addInfo("Subscription Data", $subscription_array );			
	try{
	//Subscription created
			$subscription = \Stripe\Subscription::create($subscription_array);
		}catch(\Stripe\Error\Card $e) {
	//Card Error\ApiConnection Send email to customer 
			$email_template =  file_get_contents(__DIR__."/../../email/payment_declined.php");
			$email_template = str_replace("%CARD_INFO%",$data["card_info"],$email_template);
			//$storeNotificationsEmail = 	$locationData["email"];		
				
			$delivered = mail($storeNotificationsEmail,$subject,$email_template,$headers);
			//Writing logs file
			$log->addInfo("Sending notification due to failure, Customer : $customer_id , Delivered : $delivered");
			$log->addError("Credit Card Error",$data["card_info"]);
			$log->addInfo("****** END EXECUTION **********");	
						
		} catch (\Stripe\Error\RateLimit $e) {		  // Too many requests made to the API too quickly

			$internalError = true;
			$internalMsg  = $e->getMessage();
			
		} catch (\Stripe\Error\InvalidRequest $e) {// Invalid parameters were supplied to Stripe's API
			$internalError = true;
			$internalMsg  = $e->getMessage();
		  
		} catch (\Stripe\Error\Authentication $e) { // Authentication with Stripe's API failed
		  // (maybe you changed API keys recently)
			$internalError = true;
			$internalMsg  = $e->getMessage();
		 
		} catch (\Stripe\Error\ApiConnection $e) {// Network communication with Stripe failed
			$internalError = true;
			$internalMsg  = $e->getMessage();
		  
		} catch (\Stripe\Error\Base $e) { // Display a very generic error to the user, and maybe send
		  // yourself an email
			$internalError = true;
			$internalMsg  = $e->getMessage();
		 
		} catch (Exception $e) { // Something else happened, completely unrelated to Stripe
			$internalError = true;
			$internalMsg  = $e->getMessage();
		 
		}
		if($internalError){			
			$message = "Something went wrong creating a new subscription, completely the process manually .<br> " .
						"Data : <br> StoreId : ".$data["store_id"]."<br>".
						"Client : ".$data["client"] ."<br>".
						"Campaign : ".$data["campaign_name"]."<br>".
						"Budget : ".$data["amount"]."<br>".
						"Starting At : ".$data["start_date"]."<br>".
						"Card Info : ". $data["card_info"] ."<br>";
						
			mail($userNotifications,$subject,$message,$headers);			
			$log->addError("Internal Error setting the Subscription, Customer : $customer_id , Plan : $STRIPEPLAN");		
			$log->addError($internalMsg, $data);
			$log->addInfo("****** END EXECUTION **********");
			exit;
		}
		$log->addInfo("Subscription created successfully Id : ". $subscription->id);	
//Subscription successfully created
		$queryUpdateIncrement = "UPDATE advtrack.adwords_budget_increase SET subscription_id = '".$subscription->id ."' WHERE id = $request_id";
		//$conn->query($queryUpdateIncrement
		//$queryUpdateIncrement
		if(!$conn->query($queryUpdateIncrement)){
			$message = "Something went wrong saving subscriptionId, update it manually ".$queryUpdateIncrement;
			$log->addError($message);
			mail($userNotifications,$subject,$message,$headers);
		}
			
//Update Costs Table If client has fix cost			
		if($adwords_setup["fixed_cost"] == "Y"){
			$q_new_increment =  $conn->prepare("INSERT INTO advtrack.adcost_new (client, campid, cost, start, end , portal, type_cost, campaignId) ".
									"VALUES (?,?,?,?,?,?,?,?)");
			$cost = ($data["new_daily_budget"]/1000000) * $adwords_markup["markup"];
			$locationId = $data["client"]."-".$data["store_id"];
			$campId = $adwords_setup["campid"];
			$startDate = $data["start_date"];
			//default time for a subscription 1 year
			$endDate = date( "Y-m-d", strtotime("$startDate +1 year" ) );
			$cname="ppc";
			$ctype="I";
			$campaignId = $data["campaignId"];
			$adwords_setup["first_cycle_day"] =  $adwords_setup["first_cycle_day"] == "" ? "1" : $adwords_setup["first_cycle_day"]  ;
			$adwords_setup["last_cycle_day"] =  $adwords_setup["last_cycle_day"] == "" ? "1" : $adwords_setup["last_cycle_day"]  ;
			$cycles = splitIntoCycles($startDate, $endDate, $adwords_setup["first_cycle_day"], $adwords_setup["last_cycle_day"]);
			foreach($cycles as $cycle){	
				$cycle_start = $cycle["start"];
				$cycle_end = $cycle["end"];
				$q_new_increment->bind_param("ssdsssss", $locationId, $campId,
											$cost, $cycle_start,$cycle_end, $cname, $ctype ,$campaignId);
				
				if(!$q_new_increment->execute()){
					$message = "Error setting cost at AdCost Table client: amount : $cost ,  start_date: $cycle_start, end_date : $cycle_end , Error : ".$q_new_increment->error;
					$log->addError($message);		
					mail($userNotifications,$subject. "Setting up cost",$message,$headers);
				}
			}
		}
		$log->addInfo("Setting new cost data",$cycles);	
			
			$config = __DIR__ ."/../../googleads_v201806/config/adsapi_php.ini";	
			
//Generate a refreshable OAuth2 credential for authentication.
			
			$adwordsCustomerId = $adwords_setup["customerid"] ;  //From advtrack.adwords_setup, if there is multiple Adwords Customer Id 
																//then set up multiple times the client with the correspondent custumerId
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
					->withClientCustomerId($adwordsCustomerId)
					->build();
					
			$campaignId = "119734734"; //Test Data
			
			$campaignService = (new AdWordsServices())->get($session, CampaignService::class);
			$queryCampaign = (new ServiceQueryBuilder())->select(['Status','Amount','BudgetId','IsBudgetExplicitlyShared'])
					->where("Id")					
					->equalTo($campaignId)
					->limit(0, 1)
					->build();		
			
			$pageCampaign = $campaignService->query(sprintf('%s', $queryCampaign));
			
			
//Setting up new Adwords Budget;
			
			if(!count($pageCampaign->getEntries())){
				$message = "Error retrieving canpaign data from Adwords";
				$log->addError($message);				
				mail($userNotifications,$subject .$locationId ,$message.$campaignId,$headers);
				$log->addInfo("****** END EXECUTION **********");
				exit;
			}
			$campaignObject = $pageCampaign->getEntries()[0];	
			$log->addInfo("Retrieving campaign data from Adwords Id: $campaignId , status : ".$campaignObject->getStatus());
//Retrieving Budget Data			
			$budgetid = $campaignObject->getBudget()->getBudgetId();
			
			$currentBudget = $campaignObject->getBudget()->getAmount()->getMicroAmount();			
					
			$budgetService = (new AdWordsServices())->get($session, BudgetService::class);
		
# Create the shared budget (required).

			$budget = new Budget();
			$budget->setBudgetId($budgetid);
			$money = new Money();
			
//Current daily budget plus increment
			$cdbudget = 10000;
			$new_campaign_daily_budget = ($currentBudget + $cdbudget);
			$money->setMicroAmount($new_campaign_daily_budget);
			$budget->setAmount($money);
		
			# Create a budget operation.
			$operationBudget = new BudgetOperation();
			$operationBudget->setOperand($budget);
			$operationBudget->setOperator(Operator::SET);	
			
//If the campaign is paused update status

			try{
				$resultBudget = $budgetService->mutate([$operationBudget]);
				if($resultBudget->getPartialFailureErrors() !== null){
					$error = $resultBudget->getPartialFailureErrors()[0];
					$message = "Error updating Budget on Adwords for this campaign error : ".$error->getErrorString();
					$log->addError($message);		
					mail($userNotifications,$subject.$locationId,$message,$headers);
					$log->addInfo("****** END EXECUTION **********");
					exit;
				}
				
				$campUpdates = "dailyBudget = ".$resultBudget->getValue()[0]->getAmount()->getMicroAmount();
				
				if($campaignObject->getStatus() != "ENABLED"){
					$log->addInfo("Enabling campaign on  Adwords Id: $campaignId , status : ".$campaignObject->getStatus());
					$newCampaign = new Campaign();
					$newCampaign->setId($campaignId);
					$newCampaign->setStatus("ENABLED");
					$operationCamp = new CampaignOperation();
					$operationCamp->setOperand($newCampaign);
				
					$operationCamp->setOperator(Operator::SET);
					$newStatus = $campaignService->mutate([$operationCamp]);
					if($newStatus->getPartialFailureErrors() !== null){
						$error = $newStatus->getPartialFailureErrors()[0];
						$message = " Error updating Campaign Status on Adwords for this campaign error : ".$error->getErrorString();
						$log->addError($message);		
						mail($userNotifications,$subject.$locationId,$message,$headers);
					}else{
						$campUpdates .= ", campaignStatus ='". $newStatus->getValue()[0]->getStatus()."'";
					}
				}
			}catch (Exception $ex){
				$log->addError($ex->getMessage());		
				mail($userNotifications,$subject.$locationId,"Something went wrong setting new budget or enabling the campaign Error:".$ex->getMessage(),$headers);
				$log->addInfo("****** END EXECUTION **********");
				exit;
			}			
			$login_token =$conn->query("select * from ".$database.".storelogin where storeid='".$data["store_id"]."'");
			$payment_template =  file_get_contents(__DIR__."/../../email/payment_successful.php");
			
			if($login_token->num_rows){
				$token = $login_token->fetch_assoc()["token"];
				$payment_template = str_replace("%TOKEN%",$token ,$payment_template);
			}else{
				$payment_template = str_replace("token=%TOKEN%&" ,"",$payment_template);
			}			
			//card_info
			$payment_template = str_replace("%CARD_INFO%",$data["card_info"],$payment_template);
			//AMOUNT_INCREMENT
			$payment_template = str_replace("%AMOUNT_INCREMENT%",$data["amount"],$payment_template);
			//Start Date
			$payment_template = str_replace("%DATE_REQUESTED%",date("M/d/Y", strtotime($data["start_date"])),$payment_template);
			$amount_billed = ($data["amount"] * 1.029) + (0.30);
			$amount_billed = isset($prorrated_amount_billed) ? round($prorrated_amount_billed,2) : round($amount_billed,2);
			$prorrated_amount_billed = isset($prorrated_amount_billed) ? round($prorrated_amount_billed,2) : "0.00";
			$payment_template = str_replace("%AMOUNT_BILLED%",$amount_billed,$payment_template);
			$payment_template = str_replace("%PRORRATED_AMOUNT_BILLED%",$prorrated_amount_billed,$payment_template);
			$next_payment =  (new DateTime($next_cycle))->modify('-1 day');
			$payment_template = str_replace("%NEXT_CYCLE%",$next_payment->format("M/d/Y"),$payment_template);
			//send email notification;
			$conn->query("UPDATE advtrack.adwords_campaigns SET ".$campUpdates." WHERE campaignId = $campaignId");
			$log->addInfo("Sending confirmation email");
			mail($storeNotificationsEmail,$subject,$payment_template,$headers);
			$log->addInfo("****** END EXECUTION **********");
			exit;
