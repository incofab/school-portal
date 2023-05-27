<?php
namespace App\Helpers;

use App\Models\User;
use App\Core\CodeGenerator;
use App\Models\Subscription;
use App\Models\SubscriptionPaymentReference;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Arr;
use App\Models\PaymentReference;
use App\Models\Deposit;
use App\Models\LicensePayment;

class HandlePaymentCallback
{
    private $paystackHelper;
    private $raveHelper;
    private $subscriptionHelper;
    private $depositHelper;
    private $licensePaymentHelper;
    private $monnifyHelper;
    
    public function __construct(
        \App\Core\PaystackHelper $paystackHelper,
        \App\Core\RaveHelper $raveHelper,
        \App\Core\MonnifyHelper $monnifyHelper,
//         \App\Helpers\SubscriptionHelper $subscriptionHelper,
        \App\Helpers\DepositHelper $depositHelper,
//         \App\Helpers\LicensePaymentHelper $licensePaymentHelper,
        CodeGenerator $codeGenerator
    ){
        $this->paystackHelper = $paystackHelper;
        $this->raveHelper = $raveHelper;
        $this->monnifyHelper = $monnifyHelper;
//         $this->subscriptionHelper = $subscriptionHelper;
        $this->depositHelper = $depositHelper;
//         $this->licensePaymentHelper = $licensePaymentHelper;
    }
    
    function handlePaystackCallback($post)
    {
        $reference = Arr::get($post, 'reference');
        
        $paymentRef = PaymentReference::where('reference', '=', $reference)->with(['user'])->first();
        
        if(!$paymentRef) return ret(false, 'Paystack record not found');
        
        $ret = $this->paystackHelper->verifyReference($reference);
        
        if(!$ret[SUCCESSFUL]) return $ret;
        
        $amount = $ret['amount'];
        
        if($amount < $paymentRef['amount']){
            return ret(false, 'The amount paid is less than expected amount');
        }
        
        if($paymentRef['payment_type'] == SubscriptionPlan::class)
        {
            $ret = $this->subscriptionHelper->recordCardPayment($paymentRef, MERCHANT_PAYSTACK);
        }
        elseif($paymentRef['payment_type'] == LicensePayment::class)
        {
            $ret = $this->licensePaymentHelper->recordCardPayment($paymentRef);
        }
        elseif($paymentRef['payment_type'] == Deposit::class)
        {
            $ret = $this->depositHelper->recordCardPayment($paymentRef, $amount, MERCHANT_PAYSTACK);
        }
        
        $ret['payment_type'] = $paymentRef['payment_type'];
        return $ret;
    }
    
    function handleRaveCallback($post)
    {
        $ref = Arr::get($post, 'txref');
        
        $paymentRef = PaymentReference::
        where('reference', '=', $ref)->with(['user'])->first();
        
        if(!$paymentRef) return ret(false, 'Invalid reference');
        
        if($paymentRef['status'] !== STATUS_PENDING)
        {
            return ret(false, 'Already resolved');
        }
        
        $ret = $this->raveHelper->verifyReference($ref);
        
        if(!$ret[SUCCESSFUL]) return $ret;
        
        $amount = $ret['amount'];
        
        if($amount < $paymentRef['amount']){
            return ret(false, 'The amount paid is less than expected amount');
        }
        
        if($paymentRef['payment_type'] == SubscriptionPlan::class)
        {
            $ret = $this->subscriptionHelper->recordCardPayment($paymentRef, MERCHANT_RAVE);
        }
        elseif($paymentRef['payment_type'] == Deposit::class)
        {
            $ret = $this->depositHelper->recordCardPayment($paymentRef, $amount, MERCHANT_RAVE);
        }
        
        return $ret;
    }
    
    function handleMonnifyCallback($post)
    {
        $reference = Arr::get($post, 'reference');
        
        $paymentRef = PaymentReference::where('reference', '=', $reference)->with(['user'])->first();
        
        if(!$paymentRef) return ret(false, 'Monnify payment record not found');
        
        $ret = $this->monnifyHelper->verifyReference($reference);
        
        if(!$ret[SUCCESSFUL]) return $ret;
        
        $amount = $ret['amount'];
        
        if($amount < $paymentRef['amount']){
            return ret(false, 'The amount paid is less than expected amount');
        }
        
        if($paymentRef['payment_type'] == SubscriptionPlan::class)
        {
            $ret = $this->subscriptionHelper->recordCardPayment($paymentRef, MERCHANT_PAYSTACK);
        }
        elseif($paymentRef['payment_type'] == LicensePayment::class)
        {
            $ret = $this->licensePaymentHelper->recordCardPayment($paymentRef);
        }
        elseif($paymentRef['payment_type'] == Deposit::class)
        {
            $ret = $this->depositHelper->recordCardPayment($paymentRef, $amount, MERCHANT_PAYSTACK);
        }
        
        $ret['payment_type'] = $paymentRef['payment_type'];
        return $ret;
    }
    
    function apiVerifyLicensePayment($post)
    {
        $merchant = Arr::get($post, 'merchant');
        $reference = Arr::get($post, 'reference');
        $post['txref'] = $reference;
        
        if($merchant === MERCHANT_MONNIFY){
            $ret = $this->handleMonnifyCallback($post);
        }
        else if($merchant === MERCHANT_RAVE){
            $ret = $this->handleRaveCallback($post);
        }else{
            $ret = $this->handlePaystackCallback($post);
        }
        
        return $ret;
    }
        
    
}

