<?php
namespace App\Core;

use Illuminate\Support\Arr;

class PaystackHelper
{
    const PERCENTAGE_CHARGE = 1.5;
    const FLAT_CHARGE = 100;
    const FLAT_CHARGE_ELIGIBLE = 2500;
    
    function initialize($amount, $email, $callbackUrl, $reference = null)
    {
        $url = 'https://api.paystack.co/transaction/initialize';
        
        // Add paystack charge
        $amount = $this->addPaystackCharge($amount);
        
        // Change to kobo
        $amount = $amount * 100;
        
        $data = [
            'amount'=>$amount,
            'email'=>$email,
            'callback_url' => $callbackUrl
        ];
        
        if($reference) $data['reference'] = $reference;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".PAYSTACK_SECRET_KEY,
            "content-type: application/json",
            "cache-control: no-cache"
        ]);
        $request = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
//         dlog($request);
        if ($request)
        {
            $result = json_decode($request, true);
        }
        else
        {
            return [SUCCESSFUL => false, MESSAGE => $error];
        }
        
        if(!Arr::get($result, 'status'))
        {
            return [SUCCESSFUL => false, MESSAGE =>
                'Error: '.Arr::get($result, 'message', 'Paystack initialization failed')];
        }
        
        $data = Arr::get($result, 'data');
        
        if(!Arr::get($data, 'authorization_url'))
        {
            return [SUCCESSFUL => false, MESSAGE => Arr::get($data, 'gateway_response', 'Paystack initialization failed')];
        }
        
        return [
            SUCCESSFUL => true,
            'redirect_url' => Arr::get($data, 'authorization_url'),
            'reference' => Arr::get($data, 'reference'),
            MESSAGE => 'Reference verified'
        ];
    }
    //abandoned
    //success
    //
    function verifyReference($reference) 
    {
        $url = 'https://api.paystack.co/transaction/verify/'.$reference;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".PAYSTACK_SECRET_KEY
        ]);
        $request = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
//         dlog($request);
        if ($request) 
        {
            $result = json_decode($request, true);
        }
        else 
        {
            return [SUCCESSFUL => false, MESSAGE => $error];
        }
        
        if(!Arr::get($result, 'status'))
        {
            return [SUCCESSFUL => false, MESSAGE => 'Transaction NOT successful', 'result' => $result];
        }
        
        $data = Arr::get($result, 'data');
        
        $status = Arr::get($data, 'status');
        
        if($status !== 'success')
        {
            return [SUCCESSFUL => false, 'result' => $result, 'status' => $status,
                MESSAGE => Arr::get($data, 'gateway_response', 'Transaction NOT successful')];
        }
        
        // Getting here means payment was successful
        $amount = (int)($data['amount']/100);
        
        if($amount < 1)
        {
            return [SUCCESSFUL => false, MESSAGE => 'Invalid amount', 'status' => $status];
        }
        
        return [SUCCESSFUL => true, 'result' => $result, 'status' => $status,
            'amount' => $amount, MESSAGE => 'Reference verified'];
    }
    
    function addPaystackCharge($amount) 
    {
        $amount = (int)$amount;
        if(empty($amount)) return 0;
        
        $finalAmount = $amount;
        
        if($amount >= self::FLAT_CHARGE_ELIGIBLE) $finalAmount = $amount + self::FLAT_CHARGE;
        
        return ceil($finalAmount / (1 - (self::PERCENTAGE_CHARGE/100)));
    }
    
    function removePaystackCharge($chargedAmount) 
    {
        $chargedAmount = (int)$chargedAmount;
        if(empty($chargedAmount)) return 0;
        
        $amount = floor($chargedAmount * (1 - (self::PERCENTAGE_CHARGE/100)));
        
        if($amount >= self::FLAT_CHARGE_ELIGIBLE) $amount = $amount - self::FLAT_CHARGE;
        
        return $amount;
    }
    
    function testPaystackCharges() 
    {
        $str = '';
        $i = 0;
        $enteredAmount = 2000;
        $addCharge = $this->addPaystackCharge($enteredAmount);
        $removeCharge = $this->removePaystackCharge($addCharge);
        $i++;        
        $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 3000;
        $addCharge = $this->addPaystackCharge($enteredAmount);
        $removeCharge = $this->removePaystackCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 5500;
        $addCharge = $this->addPaystackCharge($enteredAmount);
        $removeCharge = $this->removePaystackCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 800;
        $addCharge = $this->addPaystackCharge($enteredAmount);
        $removeCharge = $this->removePaystackCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 'dsk';
        $addCharge = $this->addPaystackCharge($enteredAmount);
        $removeCharge = $this->removePaystackCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        dDie($str);
    }
    
}