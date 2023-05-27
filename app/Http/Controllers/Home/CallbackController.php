<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPaymentReference;
use App\Helpers\AirtimePaymentHelper;
use App\Models\PaymentReference;
use App\Models\LicensePayment;
use App\Models\User;

class CallbackController extends Controller
{
    private $handlePaymentCallback;
    
    /**
     * Create a new controller instance.
     *0
     * @return void
     */
    public function __construct(
        \App\Helpers\HandlePaymentCallback $handlePaymentCallback
    ){
        $this->handlePaymentCallback = $handlePaymentCallback;
    }
    
    function paystackCallback(Request $request)
    {
        $ret = $this->handlePaymentCallback->handlePaystackCallback($request->all());
        
        if(Arr::get($ret, 'payment_type') == LicensePayment::class){
            return $this->showLicenseAndActivationKeys($ret);
        }
        
        if ($user = Auth::user())
        {
            if($user->isAgent()) return redirect(route('agent-dashboard'))->with(MESSAGE, $ret[MESSAGE]);
            
            return redirect(route('user-dashboard'))->with(MESSAGE, $ret[MESSAGE]);
        }
        
        return redirect(route('home'))->with(MESSAGE, $ret[MESSAGE]);
    }
    
    function raveCallback(Request $request)
    {
        $ret = $this->handlePaymentCallback->handleRaveCallback($request->all());
        
        if(Arr::get($ret, 'payment_type') == LicensePayment::class){
            return $this->showLicenseAndActivationKeys($ret);
        }
        
        if ($user = Auth::user()) 
        {
            if($user->isAgent()) return redirect(route('agent-dashboard'))->with(MESSAGE, $ret[MESSAGE]);
            
            return redirect(route('user-dashboard'))->with(MESSAGE, $ret[MESSAGE]);
        }
        
        return redirect(route('home'))->with(MESSAGE, $ret[MESSAGE]);
    }
    
    function monnifyCallback(Request $request)
    {
        $ret = $this->handlePaymentCallback->handleMonnifyCallback($request->all());
        
        if(Arr::get($ret, 'payment_type') == LicensePayment::class){
            return $this->showLicenseAndActivationKeys($ret);
        }
        
        if ($user = Auth::user()) 
        {
            if($user->isAgent()) return redirect(route('agent-dashboard'))->with(MESSAGE, $ret[MESSAGE]);
            
            return redirect(route('user-dashboard'))->with(MESSAGE, $ret[MESSAGE]);
        }
        
        return redirect(route('home'))->with(MESSAGE, $ret[MESSAGE]);
    }
    
    private function showLicenseAndActivationKeys($data) 
    {
        if(empty($data['activation_code']) && empty($data['pin'])){
            return redirect(route('home'))->with('message', $data[MESSAGE]);
        }
        
        return view('home.offline.show-pin', ['data' => $data]);
    }
    
    function checkPaymentStatus(
        \App\Helpers\SubscriptionHelper $subscriptionHelper,
        $monnifyHelper
    ){
        if(!$_POST)
        {
            return $this->view('home/check_payment_status', [
                'username' => Arr::get($_GET, 'username'),
                'redirect' => Arr::get($_GET, 'redirect')
            ]);
        }
        
        $username = Arr::get($_POST, 'username');
        $redirect = Arr::get($_POST, 'redirect');
        $reference = Arr::get($_POST, 'reference');
        
        $userdata = User::where('username', '=', $username)->first();
        
        if($userdata) $_POST['user_id'] = $userdata['id'];
        
        if(!empty($_POST['is_reserved_account']))
        {
            $ret = $monnifyHelper->verifyAndCreditReference($reference, $userdata);
            
            return redirect(empty($redirect)?null:$redirect)->with(MESSAGE, Arr::get($ret, MESSAGE));
        }
        
        $paymentRef = PaymentReference::where('reference', '=', $reference)->with(['user'])->first();
        
        if(!$paymentRef && str_contains($reference, "FLW"))
        {
            return redirect(empty($redirect)?null:$redirect)->with(MESSAGE, 'Please wait, payment has not been resolved yet');
        }
        
        if(Arr::get($paymentRef, MERCHANT) === MERCHANT_RAVE)
        {
            $ret = $this->handlePaymentCallback->handleRaveCallback($_POST);
        }
        else
        {
            $ret = $this->handlePaymentCallback->handlePaystackCallback($_POST);
        }
        
        return redirect(empty($redirect)?null:$redirect)->with(MESSAGE, Arr::get($ret, MESSAGE));
    }
    
    function cheetahpayCallback(Request $request, AirtimePaymentHelper $airtimePaymentHelper) 
    {
        $post = $request->all();
        
        if($post['private_key'] !== CHEETAHPAY_PRIVATE_KEY || $post['public_key'] !== CHEETAHPAY_PUBLIC_KEY)
        {
            die('Invalid keys supplied');
        }
        
        $ret = $airtimePaymentHelper->cheetahpayWebhook($post);
     
        die(json_encode($ret));
    }
    
}
