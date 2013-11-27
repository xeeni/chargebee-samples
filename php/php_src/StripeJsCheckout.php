<?php
/*
 * Adding ChargeBee php libraries
 */
require(dirname(__FILE__) . "/lib/ChargeBee.php");

/* 
 * Sets the environment for calling the Chargebee API.
 * You need to sign up at ChargeBee app to get this credential.
 */
ChargeBee_Environment::configure("<your-site>","<your-api-key>");


if ($_POST) {
    try {
        $result = createSubscription();
        addShippingAddress($result->subscription(), $result->customer());
        $jsonResp = array();
	
        /*
         * Forwarding to success page after successful create subscription in ChargeBee.
         */
        $queryParameters = "name=" . urlencode($result->customer()->firstName) 
                            . "&planId=" . urlencode($result->subscription()->planId);	
        $jsonResp["forward"] = "thankyou.html?" . $queryParameters;
        echo json_encode($jsonResp, true);
        
    } catch (ChargeBee_APIError $e) {
	/* ChargeBee exception is captured through APIException and 
         * the error messsage(JSON) is sent to the client.
         */        
	$jsonError = $e->getJsonObject();
        header('HTTP/1.0 ' . $jsonError["http_status_code"] . ' Error');
        print(json_encode($jsonError, true));
    } catch(Exception $e) {
	/* Other errors are captured here and error messsage (as JSON) 
         * sent to the client.
         * Note: Here the subscription might have been created in ChargeBee 
         *       before the exception has occured.
         */
	$jsonError = array("error_msg"=>"Error while creating subscription");
	header("HTTP/1.0 500 Error");
	print json_encode($jsonError,true);
    }
}


/* Creates the subscription in ChargeBee using the checkout details and 
 * stripe temporary token provided by stripe.
 */
function createSubscription() {
    
    /*
     * Constructing a parameter array for create subscription api. 
     * It will have account information, the temporary token got from Stripe and
     * plan details.
     * For demo purpose a plan with id 'annual' is hard coded.
     * Other params are obtained from request object.
     */
    $createSubscriptionParams = array(
        "planId" => "annual",
        "customer" => array(
            "email" => $_POST["email"],
            "firstName" => $_POST["first_name"],
            "lastName" => $_POST["last_name"],
            "phone" => $_POST["phone"]
        ),
        "card" => array(
            "tmp_token" => $_POST['stripeToken']
    ));

    /* 
    * Sending request to the chargebee server to create the subscription from 
    * the parameters received. The result will have customer,subscription and 
    * card attributes.
    */
    $result = ChargeBee_Subscription::create($createSubscriptionParams);
    
    return $result;
}
/*
 * Adds the shipping address to an existing subscription. The first name
 * & the last name for the shipping address is got from the customer 
 * account information.
 */
function addShippingAddress($subscription, $customer) {
   /* 
    * Adding address to the subscription for shipping product to the customer.
    * Sends request to the ChargeBee server and adds the shipping address 
    * for the given subscription Id.
    */
    $result = ChargeBee_Address::update(array(
                "subscription_id" => $subscription->id,
                "label" => "shipping_address",
                "first_name" => $customer->firstName,
                "last_name" => $customer->lastName,
                "addr" => $_POST['addr'],
                "extended_addr" => $_POST['extended_addr'],
                "city" => $_POST['city'],
                "state" => $_POST['state'],
                "zip" => $_POST['zip_code']
    ));
    $address = $result->address();
    return $address;
}
?>
