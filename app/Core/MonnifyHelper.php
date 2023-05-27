<?php
namespace App\Core;

use Illuminate\Support\Arr;
use App\Models\User;

class MonnifyHelper
{
    const PERCENTAGE_CHARGE = 1;
    const CHARGE_START_AMOUNT = 500;
    const CHARGE_STOP_AMOUNT = 10000;
//     const FLAT_CHARGE = 100;
//     const FLAT_CHARGE_ELIGIBLE = 2500;
    
    const BASE_URL = 'https://api.monnify.com/api/';//'https://sandbox.monnify.com/api/v1/';
    
    private $reservedAccount;
    private $transactionModel;
    private $depositModel;
    
    function __construct(
        \App\Models\ReservedAccount $reservedAccount,
        \App\Models\Deposit $depositModel,
        \App\Models\Transaction $transactionModel
        ) 
    {
        $this->reservedAccount = $reservedAccount;
        $this->transactionModel = $transactionModel;
        $this->depositModel = $depositModel;
    }
    
    function auth()
    {
        $ret = $this->execCurl(self::BASE_URL.'v1/auth/login', []);
        
        if(!Arr::get($ret, SUCCESSFUL))
        {
            return [SUCCESSFUL => false, MESSAGE =>
                'Error: '.Arr::get($ret, MESSAGE, 'Base Server authentication failed')];
        }
        
        $result = $ret['result'];
        
        return [
            SUCCESSFUL => true,
            'token' => $result['accessToken'],
            MESSAGE => 'Authentication successful'
        ];
    }
    
    function reserveAccount($userData) 
    {
        $create = \App\Models\ReservedAccount::insert($userData['id'], MERCHANT_MONNIFY);
        
        if(!$create[SUCCESSFUL]) return $create;
        
        $reference = $create['data']['reference'];
        
        $auth = $this->auth();
        
        if(!$auth[SUCCESSFUL]) return $auth;
        
        $token = $auth['token'];
        
        $url = self::BASE_URL.'v1/bank-transfer/reserved-accounts';
        
        $data = [
            "accountReference" => $reference,
            "accountName" => ucfirst($userData['username']).SITE_TITLE,
            "currencyCode" => "NGN",
            "contractCode" => MONNIFY_CONTRACT_CODE,
            "customerEmail" => $userData['email'],
            "customerName" => "{$userData['name']}",
        ];
        
        $ret = $this->execCurl($url, $data, "POST", $token);
        
        if(!$ret[SUCCESSFUL]) return $ret;
        
        $result = $ret['result'];
        
        $post = [
            'reference' => $result['accountReference'],
            'account_name' => $result['accountName'],
            'account_number' => $result['accountNumber'],
            'bank_name' => $result['bankName'],
            'status' => $result['status'],
        ];
        
        return \App\Models\ReservedAccount::edit($post);
    }
    
    function verifyReference($reference) 
    {
        $ret = $this->getTransactionStatus($reference);
        
        if(!$ret[SUCCESSFUL]) return [SUCCESSFUL => false, MESSAGE => $ret[MESSAGE]];
        
        $result = $ret['result'];
        $amount = $result['amountPaid'];
        $status = $result['paymentStatus'];
        
        return [SUCCESSFUL => true, 'result' => $result, 'status' => $status,
            'amount' => $amount, MESSAGE => 'Reference verified'];
    }
    
    private function getTransactionStatus($reference) 
    {
//         $url = self::BASE_URL.'v1/merchant/transactions/query?paymentReference='.urlencode($reference);
        $url = self::BASE_URL.'api/v2/transactions/'.urlencode($reference);
        
        $data = [];        
        
        return $this->execCurl($url, $data, 'GET');
    }
    
    function monnifyCallback($post, \App\Helpers\HandlePaymentCallback $handlePaymentCallback) 
    {
        $transactionReference = Arr::get($post, 'paymentReference');
        $reference = Arr::get($post, 'paymentReference');
        $amountPaid = $post['amountPaid'];
        $totalPayable = $post['totalPayable'];
        $paidOn = $post['paidOn'];
        $paymentStatus = $post['paymentStatus'];
        $accountReference = $post['product']['reference'];
        $productType = $post['product']['type'];
        
        $transactionHash = $post['transactionHash'];
        
        $recreatedHash = hash('SHA512', MONNIFY_SECRET_KEY."|$reference|$amountPaid|$paidOn|$transactionReference");
        
        if($transactionHash !== $recreatedHash)
        {
            dlog(["Error" => "Hash mismatch", "Data" => $post, 
                'transactionHash' => $transactionHash, 'recreatedHash' => $recreatedHash]);
            
            return [SUCCESSFUL => false, MESSAGE => 'Hash mismatch'];
        }
        
        if(!$productType !== 'RESERVED_ACCOUNT'){
            return $handlePaymentCallback->handleMonnifyCallback(['reference' => $reference]);
        }
        
        $reservedAcct = \App\Models\ReservedAccount::where('reference', '=', $accountReference)
            ->with(['user'])->first();
        
        if(!$reservedAcct) return [SUCCESSFUL => false, MESSAGE => 'Account record not found'];
        
//         $ret = $this->getTransactionStatus($reference);

//         if(!$ret[SUCCESSFUL]) return [SUCCESSFUL => false, MESSAGE => 'Transaction not found'];
        
//         return $this->creditMonnifyPayment($reference, $reservedAcct['user'], $amountPaid);
        return $this->verifyAndCreditReference($reference, $reservedAcct['user']);
    }
    
    function verifyAndCreditReference($reference, $userData)
    {
        if(!$userData)
        {
            return [SUCCESSFUL => false, MESSAGE => 'User record not supplied'];
        }
        if($this->transactionModel->where('reference', '=', $reference)->first())
        {
            return [SUCCESSFUL => false, MESSAGE => 'Transaction already resolved'];
        }
        
        $ret = $this->getTransactionStatus($reference);
        
        if(!$ret[SUCCESSFUL]) return [SUCCESSFUL => false, MESSAGE => $ret[MESSAGE]];
        
        $amount = $ret['result']['amountPaid'];
//         $amount = $ret['result']['amount'];
        
        return $this->creditMonnifyPayment($reference, $userData, $amount);
    }
    
    private function creditMonnifyPayment($reference, User $userData, $amount)
    {   
        $charge = $this->getCharge($amount);
        $amount = $amount - $charge;
        
        $post = [];
        $post[CHOICE_PLATFORM] = CHOICE_PLATFORM_WEBSITE;
        $post['depositor_name'] = "{$userData['name']}";
        $post['amount'] = $amount;
        $post['charge'] = $charge;
        $post['status'] = STATUS_CREDITED;
        $post['reference'] = $reference;
        $post['payment_method'] = 'Internet Banking';
        $post['bank_name'] = ucfirst(MERCHANT_MONNIFY);
        $post['merchant'] = MERCHANT_MONNIFY;
        $post['transaction_entry'] = \App\Models\Transaction::TRANSACTION_ENTRY_CREDIT;
        $post['transaction_type'] = \App\Models\Transaction::TRANSACTION_TYPE_BANK_DEPOSIT;
        
        $post['bbt'] = $userData['balance'];
        $post['bat'] = $userData['balance']+$amount;
        
        $transactionRet = \App\Models\Transaction::recordTransaction($post, $userData, $this->depositModel);
        
        if(!Arr::get($transactionRet, SUCCESSFUL)) return $transactionRet;
        
        $transaction = $transactionRet['data'];
        
        $userData->creditUser($amount);
        
        return [SUCCESSFUL => true, MESSAGE => 'Account credited successfully',
            'balance' => $userData[BALANCE]];
    }
    
    
    
    
    
    
    
    
    
    
    
    function getCharge($amount) 
    {
        $amount = (int)$amount;
     
        if(empty($amount)) return 0;
                
        if($amount <= self::CHARGE_START_AMOUNT) return 0;
        
//         if($amount >= self::CHARGE_STOP_AMOUNT) return 0;
        
        $charge = ceil((self::PERCENTAGE_CHARGE/100) * $amount);
        
        return ($charge > 100) ? 100 : $charge;
    }
    
    function execCurl($url, $data, $method = 'POST', $token = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($data)curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $auth = $token
            ?"Bearer $token"
            :"Basic ".base64_encode(MONNIFY_PUBLIC_KEY.':'.MONNIFY_SECRET_KEY);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: ".$auth,
            "content-type: application/json",
            "cache-control: no-cache"
        ]);
        
        $request = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($request)
        {
            dlog($request);
            $result = json_decode($request, true);
        }
        else
        {
            dlog($error);
            return [SUCCESSFUL => false, MESSAGE => $error];
        }
        
        if(!Arr::get($result, 'requestSuccessful'))
        {
            return [SUCCESSFUL => false, MESSAGE =>
                'Error: '.Arr::get($result, 'responseMessage', 'Operation failed')];
        }
        
        return [
            SUCCESSFUL => true,
            MESSAGE => 'Reference verified',
            'result' => Arr::get($result, 'responseBody'),
        ];
    }
    
    function test() 
    {
        $str = '';
//         $i = 0;
//         $enteredAmount = 2000;
//         $addCharge = $this->addPaystackCharge($enteredAmount);
//         $removeCharge = $this->removePaystackCharge($addCharge);
//         $i++;        
//         $str = "($i). enteredAmount=$enteredAmount <br />addCharge=$addCharge <br />removeCharge=$removeCharge";
//         $str .= '<br /><br />';
        echo ("<br />Charge 200 = ".$this->getCharge(200));
        echo ("<br />Charge 500 = ".$this->getCharge(500));
        echo ("<br />Charge 501 = ".$this->getCharge(501));
        echo ("<br />Charge 1000 = ".$this->getCharge(1000));
        echo ("<br />Charge 10000 = ".$this->getCharge(10000));
        die('Done');
    }
    
}