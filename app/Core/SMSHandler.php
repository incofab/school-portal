<?php
namespace App\Core;

class SMSHandler
{
    function __construct()
    {
        
    }
    
    function sendSMS($receiver, $msg) 
    {
        /*
        $url = 'http://www.smslive247.com/http/index.aspx';
        
        $data = array(
            'cmd'         => 'sendquickmsg',
            'owneremail'  => 'zorabay@outlook.com',
            'subacct'     => 'GIDIGADA',
            'subacctpwd'  => 'frodo1990',
            'message'     => $msg,
            'sender'      => 'Gidigada',
            'sendto'      => $receiver,
            'msgtype'     => 0,
        );
        */
        
        $url = 'http://api.smartsmssolutions.com/smsapi.php'; 
        
        $data = array(
            'username' => 'incofab',
            'password' => 'ogbunike',
            'sender' => 'Cheetahpay',
            'recipient' => $this->formatPhoneNo($receiver),
            'message' => $msg,
        );
        
        //Using Curl library
        $curl = curl_init($url);
        
        $curlOptions = [
            
            CURLOPT_RETURNTRANSFER => true,
        
            CURLOPT_CONNECTTIMEOUT => 15,
            
            CURLOPT_POST => 5,
            
            CURLOPT_POSTFIELDS => http_build_query($data),
        ];
        
        curl_setopt_array($curl, $curlOptions);
        
        $ret = curl_exec($curl);
        
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if (!$ret)
        {
            dlog($error);
            
            return [SUCCESSFUL => false, MESSAGE => 'Connection Error: Message sending failed', 'error' => $error ];
        }
        
        if(!is_string($ret))
        {
            dlog($error);
            
            return [SUCCESSFUL => false, MESSAGE => 'Error: Unknow response'];
        }
        
        if(substr($ret, 0, 2) =='OK')
        {
            return [SUCCESSFUL => true, MESSAGE => 'Message sent successfully'];
        }
        
        return [SUCCESSFUL => false, MESSAGE => 'Message sending failed'];
    }
    
    function sendMultiSMS(array $receivers, $msg) 
    {
        $joinedReceivers = '';
        
        foreach ($receivers as $phone) 
        {
            $phone = array_get($phone, PHONE_NO, $phone);
            
            $joinedReceivers .= $this->formatPhoneNo($phone) . ',';
        }
        
        $joinedReceivers = rtrim($joinedReceivers, ',');
        
        return $this->sendSMS($joinedReceivers, $msg);
    }
    
    private function formatPhoneNo($phone) 
    {
        $phone = str_replace('+', '', $phone);
        
        return '234' . ltrim($phone, '0');
    }
    
}