<?php
namespace App\Core;

class Settings
{

    private static $exchangeRate = 1;
    
    const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    const BANKS = [
        'Access Bank Nigeria Plc', 'Diamond Bank Plc',
        'Eco Bank Nigeria', 'Fidelity Bank Plc', 'First Bank of Nigeria Plc (FBN)', 
        'First City Monument Bank (FCMB)', 'Guaranty Trust Bank (GTB) Plc',
        'Heritage Bank', 'Jaiz Bank Plc', 'Keystone Bank Ltd', 
        'Skye Bank Plc', 'Stanbic IBTC Plc',
        'Sterling Bank Plc', 
        'Union Bank of Nigeria Plc', 
        'United Bank for Africa Plc (UBA)', 
        'Unity Bank Plc', 
        'WEMA Bank Plc', 
        'Zenith Bank International'
    ];
    
    static function getExchangeRate()
    {
        return self::$exchangeRate;
    }
    
    static function convertToDollar($amountInNaira)
    {
        return $amountInNaira/self::$exchangeRate;
    }
    
    static function convertToNaira($amountInDollars)
    {
        return $amountInDollars * self::$exchangeRate;
    }
    
    static function getMinimumStake()
    {
        $minStakeInNaira = 50;
        
        return self::convertToDollar($minStakeInNaira);
    }
    static function getMaximumStake()
    {
        $maxStakeInNaira = 10000;
        
        return self::convertToDollar($maxStakeInNaira);
    }
    
    static function getMaximumBetWIn()
    {
        $maxWinInNaira = 200000;
        
        return self::convertToDollar($maxWinInNaira);
    }
    
    static function getPercentage($num, $total, $decimalPlaces = 2)
    {
        if($total == 0) $total = 1;
        
        $percent = ($num/$total) * 100;
        
        return round($percent, $decimalPlaces);
    }
    
    static function splitTime($timeInSecs, $toString = false)
    {
        $hours = floor($timeInSecs/(60*60));
        $remMins = $timeInSecs%(60*60);
        $mins = floor($remMins/60);
        $sec = floor($remMins%60);
        
        return $toString ? "{$hours}hrs {$mins}mins {$sec}secs"
            : ['hours' => $hours, 'minutes'=>$mins, 'seconds'=>$sec];
    }
    
}