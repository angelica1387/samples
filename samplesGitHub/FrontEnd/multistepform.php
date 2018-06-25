<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">  
	  <link rel="stylesheet" href="cart_navs.css">	
  </head>
  <body>
  	<? include ($_SERVER['DOCUMENT_ROOT'].'/includes/nav.php'); ?>
    <div class="main location">
    	<?php
		 if (!empty($_SESSION['success'])) {
			echo '<p class="alert alert-success">'.$_SESSION['success'].'</p>';
			unset($_SESSION['success']);
		 }
		 else if (!empty($_SESSION['error'])) {
			echo '<p class="alert alert-danger">'.$_SESSION['error'].'</p>';
			unset($_SESSION['error']);
		 }
		 else if (!empty($_SESSION['warning'])) {
			echo '<p class="alert alert-warning">'.$_SESSION['warning'].'</p>';
			unset($_SESSION['warning']);
		 }
		 else if (isset($_GET['success'])) {
			echo '<p class="alert alert-success">Your changes have been successfully saved.</p>';
		 }
		 else if (isset($_GET['error'])) {
			echo '<p class="alert alert-danger">There was an error updating your changes on the website.</p>';
		 }
		 $camp_avalaible = [];
		?>
    	<h1>Adwords Campaigns </h1>		
		<div class="row">
			<div class="box">
			<div class="row">
				<div class="col-xs-12 col-md-4 col-md-offset-4" >
					<ul class="nav nav-pills nav-wizard setup-panel">
						<li class="active" style="width:33.3%" ><a class="active" href="#step-1" data-toggle="tab" >Cart</a></li>
						<li style="width:33.3%"><a href="#step-2" data-toggle="tab" disabled="disabled">Checkout</a></li>
						<li style="width:33.3%"><a href="#step-3" data-toggle="tab" disabled="disabled">Place Order</a></li>
					</ul>
				</div>
			</div>
			<div class="row">
					<div class="col-xs-12 col-md-4 col-md-offset-4" >
						<form role="form" method="post" action="">							
							<div class="row setup-content" id="step-1">
								<div class="form-group col-xs-12">
										<label >Campaign</label>
										<select class="form-control" required>
										<?php foreach($camp_avalaible as $camp ){?>
										<option value="<?=base64_encode($camp["campaignId"])?>"> <?=$locationName.$camp["field"]?></option>
										<?php }?>
										</select>
								</div>
								<div class="form-group col-xs-12">
									<label >Amount</label>
									 <div class="input-group">
										<span class="input-group-addon" id="amount-addon">$</span>
										<input type="text" class="form-control" value="<?=$_POST["in_increase"]?>" placeholder="0" name="amount" id="amount" data-camp="<?=base64_encode($camp["campaignId"])?>" aria-describedby="amount-addon" required>
									</div>
								</div>
								<div class="form-group col-xs-12">
									<label>Start Date</label>
									<input type="date" name="startdate" class="form-control" id="startdate" required>
								</div>
								<div class="form-group col-xs-12">
									<label>End Date</label>
									<input type="date" name="enddate" class="form-control" id="enddate" required>
								</div>
								<div class="col-xs-12">
									<div class="row text-center">
										<div class="col-sm-6 col-xs-6 checkout_btn">
											<button class='form-control btn btn-primary nextBtn' >Next</button>
										</div>
									</div>
								</div>
							</div>
							
							<div class="row setup-content" id="step-2">
								<div class="form-group col-xs-12">
										<label >Campaign</label>
										<select class="form-control">
										<?php foreach($camp_avalaible as $camp ){?>
										<option value="<?=base64_encode($camp["campaignId"])?>"> <?=$locationName.$camp["field"]?></option>
										<?php }?>
										</select>
								</div>
								<div class="form-group col-xs-12">
									<label >Amount</label>
									 <div class="input-group">
										<span class="input-group-addon" id="amount-addon">$</span>
										<input type="text" class="form-control" value="<?=$_POST["in_increase"]?>" placeholder="0" name="amount" id="amount" data-camp="<?=base64_encode($camp["campaignId"])?>" aria-describedby="amount-addon" required>
									</div>
								</div>
								<div class="form-group col-xs-12">
									<label>Start Date</label>
									<input type="date" name="startdate" class="form-control" id="startdate" required>
								</div>
								<div class="form-group col-xs-12">
									<label>End Date</label>
									<input type="date" name="enddate" class="form-control" id="enddate">
								</div>
								<div class="col-xs-12">
									<div class="row text-center checkout_btn">
										<div class="col-sm-6 col-xs-6">
											<button class='form-control btn btn-primary prevBtn' >Prev. </button>
										</div>
										<div class="col-sm-6 col-xs-6">
											<button class='form-control btn btn-primary nextBtn' >Next</button>
										</div>
									</div>
								</div>
							</div>
							
							<div class="row setup-content" id="step-3">
								<div class="col-xs-12">
								<h3>Order Summary</h3>
								<hr>
								<h5>Item <span id="camp_name"></span> <span class="pull-right">$<?=round($total,2)?></span></strong></h5>
								<hr>
								<h4 class="text-uppercase"><strong>Total <span class="pull-right">$<?=round($total,2)?></span></strong></h4>
								<hr>
								<h3>Payment Info</h3>	
								<? 
										if($row['customer_id']){ 
											try {
												$customer= \Stripe\Customer::retrieve($row['customer_id']);
											} catch(Stripe_CardError $e) {
											  $error1 = $e->getMessage();
											} catch (Stripe_InvalidRequestError $e) {
											  // Invalid parameters were supplied to Stripe's API
											  $error2 = $e->getMessage();
											} catch (Stripe_AuthenticationError $e) {
											  // Authentication with Stripe's API failed
											  $error3 = $e->getMessage();
											} catch (Stripe_ApiConnectionError $e) {
											  // Network communication with Stripe failed
											  $error4 = $e->getMessage();
											} catch (Stripe_Error $e) {
											  // Display a very generic error to the user, and maybe send
											  // yourself an email
											  $error5 = $e->getMessage();
											} catch (Exception $e) {
											  // Something else happened, completely unrelated to Stripe
											  $error6 = $e->getMessage();
											}
											if(!$error1 && !$error2 && !$error3 && !$error4 && !$error5 && !$error6){
												if($customer->default_source){
													echo "<p>Saved Payments</p><div class='radio-group'>"; 
													foreach($customer->sources->data as $source){ ?>
													<div class="payment clearfix radio" data-value="<?=$source->id?>">
														<span class="small text-uppercase select">Select</span>
														<i class="fa fa-cc-<?=$payment_icons[$source->brand]?> fa-2x pull-left" aria-hidden="true"></i> 
														<p class="pull-left">
															Ending: <strong><?=$source->last4?></strong><br>
															Expiration: <strong><?=$source->exp_month.'/'.$source->exp_year?></strong><br>
															<? if(isset($source->name)) echo '<br>'.strtoupper($source->name);?>
															<? if(isset($source->address_line1)) echo '<br>'.strtoupper($source->address_line1);?>
															<? if(isset($source->address_city)) echo '<br>'.strtoupper($source->address_city).' '.strtoupper($source->address_state).', '.$source->address_zip;?>
														</p>
													</div>
													<input type="hidden" name="success_customer" value="1" />
													
											<?		} ?>
												<input type="hidden" id="radio-value" name="payment" />
											</div>
											
											<?    }
												//print_r( json_encode($customer));
											}else{
												//echo $error1.'-1<br>';
												//echo $error2.'-2<br>';
												//echo $error3.'-3<br>';
												//echo $error4.'-4<br>';
												//echo $error5.'-5<br>';
												//echo $error6.'-6<br>';
											}
										?>
										
										<? }//else echo "No customer"; ?>
										<div class="new_card" style="margin-bottom: 20px;">
											<? $stripeAmount= $total*100; ?>
												 <script
													src="https://checkout.stripe.com/checkout.js" class="stripe-button"
													data-key=<?=$PKAPIKEY?>
													data-name=<?=$STRIPENAME?>
													data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
													data-locale="auto"
													data-zip-code="true"
													data-billing-address="true"
													data-label="Add New Card"
													data-panel-label="Pay " 
													data-amount=<?=$stripeAmount?>>
												  </script>
										</div >								
								</div>
								<div class="col-xs-12">
									<div class="row text-center checkout_btn">
										<div class="col-sm-6 col-xs-6">
											<button class='form-control btn btn-primary prevBtn' >Prev. </button>
										</div>
										<div class="col-sm-6 col-xs-6">
											<button class='form-control btn btn-primary' >Submit</button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="row">
			
				</div>
		
		</div>
		</div>
     </div>

    <? include ($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
  <script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.13/sorting/datetime-moment.js"></script>
  <script type="text/javascript">
		  $(document).ready(function(){
        /* Multi step form*/	
          var navListItems = $('ul.setup-panel li a'),
          allWells = $('.setup-content'),
          allNextBtn = $('.nextBtn'),
          allPrevBtn = $('.prevBtn');

            allWells.hide();

            navListItems.click(function (e) {
                e.preventDefault();
                var $target = $($(this).attr('href')),
                    $item = $(this);

                if (!$item.hasClass('disabled')) {
                    navListItems.removeClass('active');
                    $item.addClass('active');
                    allWells.hide();
                    $target.show();
                    $target.find('input:eq(0)').focus();
                }
            });

            allPrevBtn.click(function(){
                var curStep = $(this).closest(".setup-content"),
                    curStepBtn = curStep.attr("id"),
                    prevStepSteps = $('ul.setup-panel li a[href="#' + curStepBtn + '"]').parent().prev().children("a");

                    prevStepSteps.removeAttr('disabled').trigger('click');
            });

            allNextBtn.click(function(){
                var curStep = $(this).closest(".setup-content"),
                    curStepBtn = curStep.attr("id"),
                    nextStepWizard = $('ul.setup-panel li a[href="#' + curStepBtn + '"]').parent().next().children("a"),
                    curInputs = curStep.find("input,textarea,select").filter('[required]:visible'),
                   // curInputs = curStep.find("input[type='select'],input[type='text']"),
                    isValid = true;
              console.log(curStep);
              console.log(curInputs);
              return;
                $(".form-group").removeClass("has-error");
                for(var i=0; i< curInputs.length; i++){
                    if (!curInputs[i].validity.valid){
                        isValid = false;
                        $(curInputs[i]).closest(".form-group").addClass("has-error");
                    }
                }

                if (isValid)
                    nextStepWizard.removeAttr('disabled').trigger('click');
            });

            $('ul.setup-panel li a.active').trigger('click');



          });
        // Tooltips Initialization
        $(function () {
          $('[data-toggle="tooltip"]').tooltip()
        })
      });
      </script>
  </body>
</html>
