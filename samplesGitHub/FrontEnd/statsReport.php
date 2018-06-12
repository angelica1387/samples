<?php

/*
 * 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=Jun 12, 2018 
 * 
 */
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include ('head.php'); ?>
    <title>Conversions Dashboard | Local <?=$client?></title>
    <link rel="stylesheet" href="/css/monthly.css">
    <style>
        .cj-a {
            border: 1px solid transparent;
            max-width: 22px;
            padding: 0 5px 4px 0;
            border-radius: 2px;
            cursor: pointer;
        }
        .aw-status-enabled {
         background: transparent url(../enabled.png) no-repeat!important;
            margin: 7px 6px 0;
            height: 9px;
            width: 9px;
            overflow: hidden;
        }
        .aw-status-paused {
            background: transparent url(../disabled.png) no-repeat!important;
            margin: 7px 6px 0;
            height: 9px;
            width: 9px;
            overflow: hidden;
        }
        
        #map_container{
            position: relative;
          }
        #map{
              min-height: 100%;
              overflow: hidden;
              padding-top: 30px;
              position: relative;
              height: 400px;
        }
       .tooltip > .tooltip-inner {
            background-color: #FFFFFF; 
            color: #000000; 
            border: 1px solid red; 
            padding: 15px;
            font-size: 12px;
         }
		 
		 
        .loader {
          	position: fixed;
          	left: 0px;
          	top: 0px;
          	width: 100%;
          	height: 100%;
          	z-index: 9999;
          	background: url('../googleads-php-lib/spinner_preloader_2.gif') 50% 50% no-repeat rgba(255, 255, 255, 0.8);
         }
         #loading-indicator {
            width:50px;
            height: 50px;
            position:absolute;
            left:50%;
            top:50%;
            margin-top:-25px;
            margin-left:-25px;         
          }
         .better_per {
             color: green;
         }
         .bad_per {
             color: red;
         }
         .hours .day {           
            padding: 5px 5px /*!important*/;           
            
         }
         .hours .day p{
            font-size: 14px /*!important*/;
         }
         .inside_box{
             display:inline-block !important;
             width:45% ;
             align-items: center !important
         }
        .cpl_text{
             font-size: 12px !important;
         }
    </style>  
    
  </head>
  <body>
    <?php
    include ('nav.php');    
    $config = include($_SERVER['DOCUMENT_ROOT']."/googleads-php-lib/config/config.php");
    $getCampaigns = require($_SERVER['DOCUMENT_ROOT']."/googleads-php-lib/analytics/adwordsData.php");	

    $client_settings = array_merge(["client"=>$_SESSION['client']],$settings [$_SESSION['client']]);	

    $store_id = (isset($_GET["storeid"]))?$_GET["storeid"]:$_SESSION['storeid'];

    $adwordsModel = new AdwordsData($client_settings, $store_id); 

    $filter =    $_SESSION['client']."-".$store_id;    

    $date = DateTime::createFromFormat("U",strtotime("first day of last month"));

    $s_last_cycle =  isset($client_settings["first_cycle_day"])?        
                            $date->modify("+ ".($client_settings["first_cycle_day"]-1)." days") :
                            $date;


    $e_last_cycle = isset($client_settings["first_cycle_day"])?
                            new DateTime(date("Y-m-".$client_settings["last_cycle_day"])):
                            (new DateTime())->modify("last day of previous month");
    /*echo "End Cycle". "<br>";
            print_r($e_last_cycle). "<br>";*/
    $yesterday = DateTime::createFromFormat("U",strtotime( '-1 days' ));

    if((new DateTime())  <= $e_last_cycle ){  

              $s_last_cycle -> modify("-1 month");
              $e_last_cycle -> modify("-1 month");
    }

//validating cycles 
/*
if last_day and start_day are in the config file then
if the months are diferent compare against the previus cycle,
else compare against the previus month
*/
    if($_GET["analyticsStartDate"] && $_GET["analyticsEndDate"]){

                    $from = new DateTime($_GET["analyticsStartDate"]);
                    $to = new DateTime($_GET["analyticsEndDate"]);
                    $interval = $to->diff($from)->days;        
                    $prev_period_to = clone $from;
                    $prev_period_to->modify("-1 day");
                    $prev_period_from = clone $prev_period_to;
                    $prev_period_from = $prev_period_from->sub(new DateInterval("P".$interval."D"));
    }else{
                    $from = clone $s_last_cycle;
                    $to = clone $e_last_cycle;
                    $prev_period_from = clone $s_last_cycle;
                    $prev_period_from->modify('-1 month');
                    $prev_period_to = clone $e_last_cycle;


                    $prev_period_to = isset($client_settings["first_cycle_day"])?
                    $prev_period_to->modify('-1 month'):
                    DateTime::createFromFormat("Y-m-d", $prev_period_from ->format("Y-m-t"));
    }
      $previous_period = "Previous Period: ".$prev_period_from->format("M, d Y")." to ".$prev_period_to->format("M, d Y");

      $dateRanges = array(
                                                      [$from ->format("Y-m-d"), $to->format("Y-m-d") ],
                                                      [$prev_period_from->format("Y-m-d"), $prev_period_to->format("Y-m-d") ]
                                      );


      if($adwordsModel->getCampaigns($from->format("Y-m-d"),$to->format("Y-m-d")))  {

                    $comp_metrics = $adwordsModel->getCamapaignCompetitiveMetrics( $dateRanges );
                    $trendline  = $adwordsModel->getTrendLine($s_last_cycle,$e_last_cycle);           
                    $lastcycle_performance = $trendline[(count($trendline)-1)];
      }  
    //echo "<pre>"; print_r($comp_metrics); echo "</pre>";exit;
      $from = $from->format("Y-m-d");
      $to = $to->format("Y-m-d"); 
      $prev_period_from = $prev_period_from->format("Y-m-d");
      $prev_period_to = $prev_period_to->format("Y-m-d");		
			
    ?>
    <div class="main location">      
      <h1>Client Overview</h1>
        <div class="loader" ></div>
        <!-- date-range -->
        <form name="dates" id="dates" method="GET" class="form-inline"> 
            <input type="hidden" name="xt" value="ads"> 
            <span class="fieldset">
              <small class="text-uppercase">From</small>
              <input name="analyticsStartDate" value="<?=$from?>" class="form-control"  required>
            </span>
            <span class="fieldset">
              <small class="text-uppercase">to</small>
              <input name="analyticsEndDate" value="<?=$to?>" class="form-control"  required="" >
              <input type="submit" value="Go" class="btn btn-primary">
          </span>
        </form> 
        <div class="break"></div>
        <?php
          if( ($lastcycle_performance->bud_adj > 0.00) && ($adwordsModel->showIncreaseRequestMessage()) ){ 

            $budget_adj = floor( $lastcycle_performance->cost_period * $lastcycle_performance->bud_adj);       

            $new_budget = floor($lastcycle_performance->cost_period + $budget_adj) ;                    
          
            $last_cpc   =  round($lastcycle_performance->cost_period / $lastcycle_performance->t_clicks,2);

            $last_conv_rate = round($lastcycle_performance->total_leads / $lastcycle_performance->t_clicks, 2);

            $increment_clicks =  ceil($new_budget/$last_cpc) ;       

            $increment_leads =  ceil($increment_clicks * $last_conv_rate); 

            $form = "<form id='increase_form' action='xt_request_increase.php' method='POST' class='form-inline' style='display:none;'>
                          <input type='hidden' value='$filter' id ='filter' id ='filter'/>
                          <input type='hidden' name='budget_last_moth' value='".$lastcycle_performance->cost_period ."'/>
                          <div class='form-group'>
                            <label class='sr-only' for='in_increase'>Amount (in dollars)</label>
                            <div class='input-group'>
                              <div class='input-group-addon'>$</div>
                              <input type='text' class='form-control' id='in_increase' name = 'in_increase' value='".$budget_adj ."' required>
                              <div class='input-group-addon'>.00</div>
                            </div>
                          </div>
                          <input type='submit' class='btn green' value='Submit'>
                        </form>";
                 
              $message = "You missed out on %s%% of available searches due to budget in the last cycle. Increasing your budget by <strong>$%s.00</strong> will result in an estimated increase of %d leads.";
              echo " <div class='alert alert-danger alert-dismissable'>".
                              "<a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a> ".
                                sprintf($message, floor($lastcycle_performance->L_IS), $budget_adj,$increment_leads).
                               " <button id='btn_rq_increase' class='btn green'>Request Increase</button>".
                               $form.
                      "</div>" ;
            
            }
        ?>     
       
        <div class="break"></div>  
        <input type="hidden" value="<?=$filter?>" id ="filter"/>         
        <div class="break"></div>      
        <div class="row hours seven-cols analytics v2">
                <!--Search Lost IS (budget)-->
           <div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="item-box day">
                    <?php 
                        $arrow_forms = "";
                        $glyphicon = '';
                        $per_forms =  ($comp_metrics[0][0]["SI_lost_budget"]- $comp_metrics[1][0]["SI_lost_budget"]);
                        if($comp_metrics[0][0]["SI_lost_budget"] > $comp_metrics[1][0]["SI_lost_budget"]){
                            $arrow_forms = " bad_per";                             
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>"; 
                            
                        }
                        else if($comp_metrics[0][0]["SI_lost_budget"] < $comp_metrics[1][0]["SI_lost_budget"]){
                            $arrow_forms = "better_per";                          
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";     
                          
                        }                       
                    ?>
                        <img class="icon" src="search-cost.svg">
                        <p class="content-wrap text-center"><span class="item-title">Search Lost IS (budget)</span>
                         <span id="analytics_sessions"> <?= $comp_metrics[0][0]["SI_lost_budget"]?> %
                          <?=$glyphicon?>
                        </span>
                  <p class="text-center">
                            <span class="inside_box" data-toggle="tooltip" title="<?=$previous_period?>" data-placement="right">
                              <span class="sm-title">Previous Period</span>
                              <?= $comp_metrics[1][0]["SI_lost_budget"]?>%</span>
                            <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="left">
                              <span class="sm-title">Comparative Change(%)</span>                             
                              <?=round($per_forms,2)?>%
                            </span>
                        </p>
                    </p>
                </div>
            </div>
            <!--Impressions-->
            <div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="item-box day">
                     <?php 
                        $arrow_forms = "";
                        $glyphicon = '';
                        $per_forms= ($comp_metrics[1][0]["Imps"]!= 0)
                                    ?($comp_metrics[0][0]["Imps"] / $comp_metrics[1][0]["Imps"])*100
                                    :0;
                        if($comp_metrics[0][0]["Imps"] > $comp_metrics[1][0]["Imps"]){
                           $arrow_forms = "better_per";                           
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>";
                            $per_forms = ($comp_metrics[1][0]["Imps"] == 0)?100: $per_forms - 100;
                        }
                        else if($comp_metrics[0][0]["Imps"] < $comp_metrics[1][0]["Imps"]){
                            $arrow_forms = " bad_per";                           
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";
                            $per_forms = ($comp_metrics[0][0]["Imps"] == 0)?100: $per_forms - 100;
                        }
                        
                    ?>
                    <img class="icon" src="eye.svg">
                    <p class="content-wrap text-center"><span class="item-title">Impressions</span>
                        <span id="analytics_sessions"> <?= number_format($comp_metrics[0][0]["Imps"])?> 
                        <?=$glyphicon?>
                        </span>
                  <p class="text-center">
                            <span class="inside_box" data-toggle="tooltip" title="<?=$previous_period?>" data-placement="right">
                              <span class="sm-title">Previous Period</span>
                              <?= $comp_metrics[1][0]["Imps"]?>
                            </span>
                            <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="right">
                              <span class="sm-title">Comparative Change(%)</span>
                              <?=round($per_forms,2)?>%
                            </span>
                        </p>
                    </p>
                </div>
          </div>
      <!--Clicks-->
          <div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <div class="item-box day">
                       <?php 
                          $arrow_forms = "";
                          $glyphicon = '';
                           $per_forms= ($comp_metrics[1][0]["clicks"]!= 0)
                                      ?($comp_metrics[0][0]["clicks"] / $comp_metrics[1][0]["clicks"])*100
                                      :0;
                          if($comp_metrics[0][0]["clicks"] > $comp_metrics[1][0]["clicks"]){
                              $arrow_forms = "better_per";
                             
                              $glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>";
                                $per_forms = ($comp_metrics[1][0]["clicks"] == 0)?100: $per_forms - 100;

                          }
                          else if($comp_metrics[0][0]["clicks"] < $comp_metrics[1][0]["clicks"]){
                              $arrow_forms = " bad_per";
                               
                              $glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";
                              $per_forms = ($comp_metrics[0][0]["clicks"] == 0)?100: $per_forms - 100;

                          }
                          
                      ?>
                      <img class="icon" src="click.svg">
                      <p class="content-wrap text-center"><span class="item-title">Clicks (<b>CTR :<?= round(($comp_metrics[0][0]["clicks"]/ $comp_metrics[0][0]["Imps"])*100,2)?>%</b>)</span>
                          <span id="analytics_sessions"> <?= $comp_metrics[0][0]["clicks"]?> 
                            <?=$glyphicon?>
                          </span>
                    <p class="text-center">
                              <span class="inside_box" data-toggle="tooltip" title="<?=$previous_period?>" data-placement="right">
                                <span class="sm-title">Previous Period</span>
                                <?= $comp_metrics[1][0]["clicks"]?></span>
                              <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="right">
                                <span class="sm-title">Comparative Change(%)</span>                           
                                  <?=round($per_forms,2)?>%
                              </span>
                          </p>
                      </p>
                  </div>
          </div>
              <!--Conv. Rate-->
          <div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="item-box day">
                     <?php 
                        $arrow_forms = "";                        
                        $conv_rate = round($comp_metrics[0][0]["conv_rate"],2);
                        $conv_rate_2 = round($comp_metrics[1][0]["conv_rate"],2);
                        $glyphicon = '';
                        $change_conv_rate = 0;
                        if($conv_rate > $conv_rate_2){
                            $arrow_forms = "better_per";
                            $change_conv_rate = $conv_rate-$conv_rate_2 ;
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>";                            

                        }
                        else if($conv_rate < $conv_rate_2){
                            $arrow_forms = " bad_per";
                            $change_conv_rate = $conv_rate-$conv_rate_2;
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";
                        }
                        
                    ?>     
                    <img class="icon" src="conversion.svg">
                    <p class="content-wrap text-center"><span class="item-title">Conv. rate</span>
                    <span id="analytics_sessions"> <?= $conv_rate?>%
                     <?=$glyphicon?>
                    </span>
                  <p class="text-center">
                            <span class="inside_box" data-toggle="tooltip" title="<?=$previous_period?>" data-placement="right">
                              <span class="sm-title">Previous Period</span>
                              <?=$conv_rate_2?>%
                            </span>
                            <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="right">
                              <span class="sm-title">Comparative Change(%)</span>
                                <?=round($change_conv_rate,2)?>%
                            </span>
                        </p>
                    </p>
                </div>
            </div>
              <!--Total Leads-->
           <div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="item-box day">
                      <?php 
                            $arrow_forms = "";
                            $glyphicon = '';

                            $leads = $comp_metrics[0][0]["t_forms"] + $comp_metrics[0][0]["p_calls"];
                            $leads_comp = $comp_metrics[1][0]["t_forms"] + $comp_metrics[1][0]["p_calls"];

                            $per_forms= ((int)$leads_comp != 0)
                                    ?($leads / $leads_comp)*100
                                    :0;
                            if($leads > $leads_comp){

                               $arrow_forms = "better_per";                             
                               $glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>";
                               $per_forms = ((int)$leads_comp == 0)? 100: $per_forms - 100;
                               
                            }
                            else if($leads < $leads_comp){                                
                                $arrow_forms = " bad_per";                              
                                $glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";
                                $per_forms = ((int)$leads == 0)? 100: $per_forms - 100;
                            }                            
                                
                        ?>
                    <img class="icon" src="group.svg">
                    <p class="content-wrap text-center"><span class="item-title">Total Leads</span> 
                        <span id="analytics_sessions"> <?=  $leads?>
                        <?=$glyphicon?>
                        </span>
                  <p class="text-center">
                            <span class="inside_box" data-toggle="tooltip" title="<?=$previous_period?>" data-placement="right" >
                              <span class="sm-title">Previous Period</span>
                              <?=$leads_comp?></span>
                            <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="right">
                              <span class="sm-title">Comparative Change(%)</span>
                              <?=round($per_forms,2)?>%
                            </span>
                        </p>
                    </p>
          </div>
          </div>            
            <!--CPL-->
		   <?php 
				$arrow_forms = "";
				$glyphicon = '';
				$leads = $comp_metrics[0][0]["t_forms"] + $comp_metrics[0][0]["p_calls"];
				$leads_comp = $comp_metrics[1][0]["t_forms"] + $comp_metrics[1][0]["p_calls"];
				$cost = $comp_metrics[0][0]["totalCost"] ;
				$cost_comp = $comp_metrics[1][0]["totalCost"] ;
				$cpl = round($cost/$leads,2);
				$cpl_2 = round($cost_comp/$leads_comp,2);
				$cpl_region = $comp_metrics[0][0]["cpl_region"];
				
				$per_forms = ($cpl_region!= 0)
							? ($cpl / $cpl_region)*100:0;
			   
				if($cpl > $cpl_region){
					$arrow_forms = "bad_per";
					$per_forms = ($cpl_region == 0)?100: $per_forms - 100;
					$glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>";
				  
				}
				else if($cpl < $cpl_region){
					$arrow_forms = "better_per";
					$per_forms = ($cpl == 0)?100: $per_forms - 100;
					$glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";
				}
			   
			?> 
             <!--<div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="item-box day">
                    <img class="icon" src="cpl.svg">
                    <p class="content-wrap text-center"><span class="item-title">CPL / State CPL</span>
                    <span id="analytics_sessions"> $<?= $cpl?>
                    <?=$glyphicon?>
                    </span>
                  <p class="text-center">
                            <span class="inside_box" data-toggle="tooltip" title="Region CPL" data-placement="right" >
                              <span class="sm-title">State CPL</span>
                              $<?=$cpl_region?>
                            </span>
                            <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="right" >
                              <span class="sm-title">Comparative Change(%)</span>
                                <?=round($per_forms,2)?>%
                            </span>
                        </p>
                    </p>
                </div>
            </div>  -->
                  <!--National CPL-->
         <div class="campaign-item col-xs-12 col-sm-6 col-md-4 col-lg-3">
                <div class="item-box day">
                     <?php 
                        $arrow_forms = "";
                        $glyphicon = '';
                        $national_cpl =  $comp_metrics[0][0]["national_cpl"];
                        $leads = $comp_metrics[0][0]["t_forms"] + $comp_metrics[0][0]["p_calls"];
                        $cost = $comp_metrics[0][0]["totalCost"] ;                       
                        $cpl = round($cost/$leads,2);                      
                        
                        $per_forms = ($national_cpl!= 0)
                                    ? ($cpl / $national_cpl)*100:0;
                       
                        if($cpl > $national_cpl){
                            $arrow_forms = "bad_per";
                            $per_forms = ($cpl_region == 0)?100: $per_forms - 100;
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-top $arrow_forms'></i>";
                          
                        }
                        else if($cpl < $national_cpl){
                            $arrow_forms = "better_per";
                            $per_forms = ($cpl == 0)?100: $per_forms - 100;
                            $glyphicon = "<i class='glyphicon glyphicon-triangle-bottom $arrow_forms'></i>";
                        }
                       
                    ?>

                    <img class="icon" src="cpl-national.svg">
                    <p class="content-wrap text-center"><span class="item-title">CPL / National CPL</span>
                    <span id="analytics_sessions"> $<?= $cpl?>
                    <?=$glyphicon?>
                    </span>
                    <p class="text-center">
                            <span class="inside_box" data-toggle="tooltip" title="National CPL" data-placement="right" >
                              <span class="sm-title">National CPL</span>
                              $<?=$national_cpl?>
                            </span>
                            <span class="inside_box <?=$arrow_forms?>" data-toggle="tooltip" title="Comparative Change(%)" data-placement="right" >
                              <span class="sm-title">Comparative Change(%)</span>
                                <?=round($per_forms,2)?>%
                            </span>
                        </p>
                    </p> 
                </div>
        </div>             
        </div>   

        <div class="break"></div> 
           <h2>Last Cycles Campaign Overview</h2>
         <div class="row"> 
            <div>
             

                    <div class="col-xs-12 col-sm-6 col-md-4">                
                       <div id="chart1-container"></div>  
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4">
                       
                                <div>
                                     <div id="chart2-container" ></div>   
                                </div>
                           
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4">
                       
                        <div>
                             <div id="chart3-container"></div>   
                        </div>
                    </div>              
        </div>      
      </div>      
</div>
    <?php  include ($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?=MAPS_API_KEY?>" ></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>    
    <script type="text/javascript">
       var map;
       var bounds;
        var target ;
        var geoData ;
        var geoTable;
        var url = window.location.protocol + "//" +window.location.hostname;
       var filter = $("#filter").val();

       $(window).on('load', function(){
            setTimeout(removeLoader, 2000); //wait for page load PLUS two seconds.
        });        
       // google.charts.setOnLoadCallback(drawCharts);
        $(document).ready(function(){  
            
            var dateFormat = "mm/dd/yy";
            from = $( "input[name='analyticsStartDate']" )
                  .datepicker({
                    defaultDate: "-8d",
                    changeMonth: true,
                    numberOfMonths: 1,
                    dateFormat: 'yy-mm-dd',
                     maxDate: "-2d"
                  })
                  .on( "change", function() {
                    to.datepicker( "option", "minDate", getDate( this ) );
                  });
            to = $( "input[name='analyticsEndDate']" ).datepicker({
                  defaultDate: "-2d",
                  changeMonth: true,
                  numberOfMonths: 1,
                  dateFormat: 'yy-mm-dd',
                  maxDate: "-2d"
            })
            .on( "change", function() {
                  from.datepicker( "option", "maxDate", getDate( this ) );
            });

            $(function () {
              $('[data-toggle="tooltip"]').tooltip();
            });
           
            google.charts.load('current', {packages: ['corechart','table']}); 
       
            google.charts.setOnLoadCallback(drawCharts);
            
             $(window).scroll(function() {
                    if ($(this).scrollTop()>0)
                     {
                       $("#increase_form").hide();
                     }                    
             });

            $("#increase_form").focusout(function() {
              console.log("focusout");

            });

            
          
            
        });

    function getDate( element ) {
        var date;
        try {
              date = $.datepicker.parseDate( dateFormat, element.value );
        } catch( error ) {
              date = null;
        }
        //console.log(date);
        return date;
    };
    function hideAllInfoWindows(map) {
        markers.forEach(function(marker) {
        marker.infowindow.close(map, marker);
       }); 
    }
    function removeLoader(){
            $(".loader").fadeOut(500, function() {
                // fadeOut complete. Remove the loading div
                  $(".loader").remove(); //makes page more lightweight 
              });  
    } 
    function showLoader(){
            $(".loader").fadeIn(500, function() {
                // fadeOut complete. Remove the loading div
                  $(".loader").show(); //makes page more lightweight 
              });  
    } 
    function drawCharts() {

        var url = window.location.protocol + "//"+window.location.hostname+"/";
        var cpl = [];
        var ctr = [];
        var is = [];
        var formatter_short = new google.visualization.DateFormat({pattern: 'MMM'});
        var h_Axis = [];             	
        var trendline = <?=count($trendline)?json_encode($trendline):"[]"; ?>;
     
        var highiest_CPL = 0; 
        var highiest_National_CPL = 0;
        var highiest_Total_Leads = 0;
    
        $.each( trendline, function( index, value ){  
           console.log( value);
          var d = new Date(value.year, value.month, 0);
          var number_conv_rate = (parseInt(value.t_clicks) === 0)?0:((value.total_leads/value.t_clicks )*100);  
          var number_ctr = (parseInt(value.r_imps) === 0)?0:((value.t_clicks/value.r_imps)*100);  
          var conv_rate = value.total_leads/value.t_clicks;
          var avg_cpc = value.cost_period/value.t_clicks;
          var inc_clicks = value.l_imps * value.ctr;  
       
          var inc_cost = (value.bud_adj > 0.00) ? 
                         (value.cost_period * value.bud_adj) + value.cost_period :
                           0.00; 
          var inc_clicks = Math.ceil(inc_cost / avg_cpc);
          var inc_leads =  Math.ceil(inc_clicks * conv_rate);    
          highiest_CPL = Math.max(highiest_CPL,value.cpl);
          highiest_National_CPL = Math.max(highiest_National_CPL,value.national_cpl);
          highiest_Total_Leads = Math.max(highiest_Total_Leads,value.total_leads);
             //data1
          cpl.push([ value.cycle, Number(value.forms),'color:#ff5252',Number(value.calls),'color:#0488b9',parseFloat(Number(value.cpl).toFixed(2))
                    ,parseFloat(Number(value.national_cpl).toFixed(2)),Number(number_conv_rate.toFixed(2)) ]);
                
            //data2
          ctr.push([value.cycle, Number(value.r_imps),Number(value.t_clicks), Number(number_ctr.toFixed(2)),parseFloat(Number(avg_cpc).toFixed(2))]);
          //data3
          is.push([value.cycle,parseFloat(Number(value.IS).toFixed(2)),parseFloat(Number(value.L_IS).toFixed(2)), Number(value.l_imps),Number(inc_clicks),Number(inc_leads)]) ;
        });
       
      
        //Impressions Lost due Budget

        var data1 = new google.visualization.DataTable();
            data1.addColumn({type: 'string', role: 'domain'}, 'Cycles');  
            data1.addColumn('number', 'Web Leads');                                          
            data1.addColumn({type: 'string', role: 'style'});                                          
            data1.addColumn('number', 'Calls');
            data1.addColumn({type: 'string', role: 'style'});            
            data1.addColumn('number', 'CPL ($)');
           data1.addColumn('number', 'National CPL ($)'); 
            data1.addColumn('number', 'Conv. Rate (%)');
            data1.addRows(cpl);    

        var data2 =  new google.visualization.DataTable();
            data2.addColumn({type: 'string', role: 'domain'}, 'Cycles');             
            data2.addColumn('number', 'Impressions');  
            data2.addColumn('number', 'Clicks');         
            data2.addColumn('number', 'Avg. CPC ($)');           
            data2.addColumn('number', 'CTR (%)');     
                                    
            data2.addRows(ctr);               

        var data3 = new google.visualization.DataTable(); 
            data3.addColumn('string', 'Cycles');                      
            data3.addColumn('number', 'Search Impr. share (%) ');             
            data3.addColumn('number', 'Search Lost IS by Budget(%)');  
            data3.addColumn('number', 'Lost Impressions by Budget');  
            data3.addColumn('number', 'Lost Clicks by Budget');  
            data3.addColumn('number', 'Lost Leads by Budget');  
            data3.addRows(is);    

        var sett_1 = {
            title : 'Conversions',
            legend: {position:  'top' },
           /* 2: {type: 'line' ,color:'#49ff3a', targetAxisIndex: 2,lineWidth:2},
            3: {type: 'line' , pointSize:5 ,color:'#00bcd5',
                lineWidth:0,pointsVisible:true,targetAxisIndex: 3,},
            4: {type: 'line' , pointSize:5 ,scaleType: 'log',curveType: 'function',color:'#f96130',
            visibleInLegend: false, lineWidth:0,pointsVisible:false,targetAxisIndex:4,}*/
            //curveType: 'function',
            height:350,  
            //logscale: true, scaleType: 'log',curveType: 'function',                
            series: { 
                     /* 0: {color:'#0488b9',targetAxisIndex: 0,},
                      1:{color:'#ff5252',targetAxisIndex: 1,},*/
                      2: {type: 'line' ,color:'#00bcd5', targetAxisIndex: 2,lineWidth:2, pointsVisible:false, curveType: 'function'},
                      3: {type: 'line' ,color:'#49ff3a', targetAxisIndex: 2,lineWidth:2, pointsVisible:false,  visibleInLegend: false, curveType: 'function'},
                      4: {type: 'line' ,color:'#cc2028', visibleInLegend: false, lineWidth:0,pointsVisible:false,targetAxisIndex: 3,}
                    
                    },
            vAxis: {textPosition: 'none'}, 
            hAxis: { 
                    textPosition: 'none',                
             
            },      
            seriesType: 'bars', 
            isStacked: true ,
			bar: {groupWidth: "50%"},
            focusTarget: 'category'             
           }; 


       var sett_2 = {
                title : 'Performance',                    
                height:350,   
               // legend: {position:  'right' },                  
               isStacked : true, 
                hAxis: { textPosition: 'none', },
                vAxis: {textPosition: 'none',curveType: 'function'}, 
                series: { 
                            
                    0:{targetAxisIndex: 0,color:'#0488b9', lineWidth:1, areaOpacity:0.3}, 
                    1:{targetAxisIndex: 0,color:'#ff5252', lineWidth:1, areaOpacity:0.3},                     
                    2: { visibleInLegend: false,
                          color:'#cf4844',
                          lineWidth:0,
                          pointsVisible:false,
                        curveType: 'function'},
                    3: {type: 'line',targetAxisIndex: 1,   
                      color:'#00bcd5',
                       lineWidth:2,
                      pointsVisible:false,
                      areaOpacity: 0,
                       visibleInLegend: false,
                      curveType: 'function',
                   },
                  },  
                //tooltip: {isHtml: true},         
               
               focusTarget: 'category',
                seriesType : 'area',
             /*   annotations: { 
                              boxStyle: 
                                        { rx: 10,
                                         // y-radius of the corner curvature.
                                          ry: 10,
                                        },
                              gradient: {
                                      // Start color for gradient.
                                      color1: '#fbf6a7',
                                      // Finish color for gradient.
                                      color2: '#33b679',
                                      x1: '0%', y1: '0%',
                                      x2: '100%', y2: '100%',         
                                      useObjectBoundingBoxUnits: true
                                  }
                              }  */
         };


       var sett_3 = {
            title : 'Competitive Metrics',
            height:350, 
            isStacked : true,           
            hAxis: {
                    textPosition: 'none',                
             
            }, 
            vAxis: {
                    textPosition: 'none',                
             
            }, 
            series: {
                      0: {targetAxisIndex: 0,  labelInLegend:'Imps. Share(IS)(%)', curveType: 'function',pointsVisible:false, color:'#ff5252'},
                      1: {targetAxisIndex: 1,  labelInLegend:'Lost IS (Budget)(%)', curveType: 'function',pointsVisible:false,color:'#00bcd5'},
                      2: {targetAxisIndex: 2, visibleInLegend: false, color:'#cf4844', lineWidth:0,  pointsVisible:false,areaOpacity: 0,},
                      3: {targetAxisIndex: 3, visibleInLegend: false, color:'#dc8539', lineWidth:0,  pointsVisible:false,areaOpacity: 0,},
                      4: {targetAxisIndex: 4, visibleInLegend: false, color:'#b3ebf5', lineWidth:0,  pointsVisible:false,areaOpacity: 0,},
                    },
                  
            seriesType: 'line',  
            focusTarget: 'category'         
           };

    // Instantiate and draw the chart.
    var chart1 = new google.visualization.ComboChart(document.getElementById('chart1-container'));   
    chart1.draw(data1, sett_1); 
    var chart2 = new google.visualization.AreaChart(document.getElementById('chart2-container'));

    chart2.draw(data2, sett_2);  
    var chart3 = new google.visualization.ComboChart(document.getElementById('chart3-container'));
    chart3.draw(data3, sett_3);
    }
  </script>

