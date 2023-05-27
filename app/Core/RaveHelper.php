<?php
namespace App\Core;

use Illuminate\Support\Arr;

class RaveHelper
{
    const PERCENTAGE_CHARGE = 1.5;
    const FLAT_CHARGE = 100;
    const FLAT_CHARGE_ELIGIBLE = 2500;
    
    function initialize($userData, $amount, $callbackUrl, $reference)
    {
//         $url = DEV?'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/hosted_pay/':
        $url = DEV?'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/hosted/pay':
                'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay';
        
        // Add paystack charge
        $amount = $this->addCharge($amount);
        
        // Change to kobo
//         $amount = $amount * 100;
        
        $data = [
            'PBFPubKey'=> RAVE_PUBLIC_KEY,
            'amount'=>$amount,
            'currency'=>'NGN',
            'customer_email'=> Arr::get($userData, 'email', SITE_EMAIL),
            'customer_phone'=> Arr::get($userData, 'phone'),
            'txref'=> $reference,
            'redirect_url' => $callbackUrl
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
//             "Authorization: Bearer ".PAYSTACK_SECRET_KEY,
            "content-type: application/json",
            "cache-control: no-cache"
        ]);
        $request = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
//         dDie($request);
//         dlog($request);
        if ($request)
        {
            $result = json_decode($request, true);
        }
        else
        {
            return [SUCCESSFUL => false, MESSAGE => $error];
        }
        
        if(empty($result['data']['link']))
        {
            return [SUCCESSFUL => false, MESSAGE => 'Initialization failed'];            
        }
        
        return [
            SUCCESSFUL => true,
            'redirect_url' => $result['data']['link'],
            'reference' => $reference,
            MESSAGE => 'Reference verified'
        ];
    }
    
    //abandoned
    //success
    //
    function verifyReference($reference) 
    {
        $url = DEV ? 'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify': 
            'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify';
        
        $data = [
            'SECKEY' => RAVE_SECRET_KEY,
            'txref' => $reference
        ];
            
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //             "Authorization: Bearer ".PAYSTACK_SECRET_KEY,
            "content-type: application/json",
            "cache-control: no-cache"
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response) 
        {
            $result = json_decode($response, true);
        }
        else 
        {
            return [SUCCESSFUL => false, MESSAGE => $error];
        }

        $status = Arr::get($result, 'status');
        
        if($status !== 'success' || empty($result['data']['status']) 
            || $result['data']['status'] !== 'successful')
        {
            return [SUCCESSFUL => false, 'result' => $result, 'status' => $status,
                MESSAGE => 'Transaction NOT successful'];
        }
        
        $data = Arr::get($result, 'data');
        
        // Getting here means payment was successful
        $amount = $data['amount'];
        
        if($amount < 1)
        {
            return [SUCCESSFUL => false, MESSAGE => 'Invalid amount', 'status' => $status];
        }
        
        if($data['currency'] !== 'NGN')
        {
            return [SUCCESSFUL => false, MESSAGE => 'Invalid Currency', 'status' => $status];
        }
        
        return [SUCCESSFUL => true, 'result' => $result, 'status' => $status,
            'amount' => $amount, MESSAGE => 'Reference verified'];
    }
    
    function addCharge($amount) 
    {
        return $amount;
        if(empty((int)$amount)) return 0;
        
        $finalAmount = $amount;
        
        if($amount >= self::FLAT_CHARGE_ELIGIBLE) $finalAmount = $amount + self::FLAT_CHARGE;
        
        return ceil($finalAmount / (1 - (self::PERCENTAGE_CHARGE/100)));
    }
    
    function removeCharge($chargedAmount) 
    {
        return $chargedAmount;
        if(empty((int)$chargedAmount)) return 0;
        
        $amount = floor($chargedAmount * (1 - (self::PERCENTAGE_CHARGE/100)));
        
        if($amount >= self::FLAT_CHARGE_ELIGIBLE) $amount = $amount - self::FLAT_CHARGE;
        
        return $amount;
    }
    
    function testPaystackCharges() 
    {
        $str = '';
        $i = 0;
        $enteredAmount = 2000;
        $addCharge = $this->addCharge($enteredAmount);
        $removeCharge = $this->removeCharge($addCharge);
        $i++;        
        $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 3000;
        $addCharge = $this->addCharge($enteredAmount);
        $removeCharge = $this->removeCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 5500;
        $addCharge = $this->addCharge($enteredAmount);
        $removeCharge = $this->removeCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 800;
        $addCharge = $this->addCharge($enteredAmount);
        $removeCharge = $this->removeCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        $enteredAmount = 'dsk';
        $addCharge = $this->addCharge($enteredAmount);
        $removeCharge = $this->removeCharge($addCharge);
        $i++;        
        $str .= "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
        $str .= '<br /><br />';
        
        dDie($str);
    }
    
}