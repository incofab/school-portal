<?php
namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;

class WebhookController extends Controller
{
    private $handlePaymentCallback;
    
    function __construct(
        \App\Helpers\HandlePaymentCallback $handlePaymentCallback
    ){
        $this->handlePaymentCallback = $handlePaymentCallback;
	}
	
	function paystackWebhook()
	{
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' )
            || !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER) ){
                dlog('paystackWebhook: Method not post or Signature not found');
                exit();
        }
        
        // Retrieve the request's body
        $input = @file_get_contents("php://input");
        
        // validate event do all at once to avoid timing attack
        if(Arr::get($_SERVER, 'HTTP_X_PAYSTACK_SIGNATURE') !== hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY)){
            dlog('paystackWebhook: Signature validation failed');
            exit();
        }
        
        http_response_code(200);
        
        // parse event (which is json string) as object
        // Do something - that will not take long - with $event
        $event = json_decode($input, true);
//         dlog('Paystack webhook below');
//         dlog($event);
        
        if(Arr::get($event, 'event') != 'charge.success') exit();
        
        $data = Arr::get($event, 'data');
        
        if(Arr::get($data, 'status') != 'success') exit();
        
        $customer = Arr::get($data, 'customer');
        $email = Arr::get($customer, 'email');
        $amount = Arr::get($customer, 'amount');
        $amount = (int)($data['amount']/100);
        
        if(!$email) exit();
        
        $reference = $data['reference'];
        
        $ret = $this->handlePaymentCallback->handlePaystackCallback($data);

//         dlog("reference = $reference, email = $email, amount = $amount".PHP_EOL.json_encode($ret, JSON_PRETTY_PRINT));
        
        exit(Arr::get($ret, MESSAGE));
	}
	
	function raveWebhook()
	{        
        // Retrieve the request's body
        $body = @file_get_contents("php://input");
        
        // retrieve the signature sent in the reques header's.
        $signature = (isset($_SERVER['HTTP_VERIF_HASH']) ? $_SERVER['HTTP_VERIF_HASH'] : '');
        
        /* It is a good idea to log all events received. Add code *
         * here to log the signature and body to db or file       */
        
        if (!$signature) {
            // only a post with rave signature header gets our attention
//             dlog('raveWebhook(): Signature not found');
            exit('No signature found');
        }
        
        // Store the same signature on your server as an env variable and check against what was sent in the headers
        
        // confirm the event's signature
        if( $signature !== HASH_KEY ){
            // silently forget this ever happened
            exit('Signature mismatch');
        }
         
        http_response_code(200);
        
        $response = json_decode($body, true);
        
        if(Arr::get($response, 'status') !== 'successful'){
            exit('Not successful');
            return;
        }
        
        $reference = Arr::get($response, 'txRef');
        $amount = Arr::get($response, 'amount');
        
        $ret = $this->handlePaymentCallback->handleRaveCallback(['txref' => $reference]);

        exit('Okay');
	}
	
	function monnifyWebhook(
	    \App\Core\MonnifyHelper $monnifyHelper
    ){
        $body = @file_get_contents("php://input");
        $response = json_decode($body, true);
//         dlog($body);
	    $ret = $monnifyHelper->monnifyCallback($response);
	    
	    if(!Arr::get($ret, SUCCESSFUL)) dlog($ret);
	    
        exit('Okay');
	}
	
	
}