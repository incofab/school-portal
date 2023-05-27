<?php
namespace App\Core;

class CodeGenerator
{
    
    const alphUpper = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    ];
    
    const alphLower = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
    ];
    
    const digits = [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, ];
    
    static function generateCodes($num) 
    {
        $codes = '';
        for ($i = 0; $i < $num; $i++) 
        {
//             $mergedArr = array_merge(self::alphUpper, self::alphLower);
            $mergedArr = array_merge(self::alphUpper, self::digits);
            $codes .= $mergedArr[mt_rand(0, count($mergedArr) - 1)];
        }
        
        return $codes;
    }
    
    static function generateRandomAlphabets($num = 1, $caseSensitive = true)
    {
        $count = 0;
        $ret = '';
        while ($count < $num)
        {
            $ret .= self::generateRandomAlphabet($caseSensitive);
            $count++;
        }
        
        return $ret;
    }
    
    private static function generateRandomAlphabet($caseSensitive = true)
    {        
        if($caseSensitive)
        {
            $mergedArr = array_merge(self::alphUpper, self::alphLower);
            $ret = $mergedArr[mt_rand(0, count($mergedArr) - 1)];
        }
        else
        {
            $ret = self::alphUpper[mt_rand(0, count(self::alphUpper) - 1)];
        }
        
        return $ret;
    }
    
    static function generateRandomDigits($num = 1)
    {        
        $count = 0;
        $ret = '';
        while ($count < $num) 
        {
            $ret .= self::digits[mt_rand(0, count(self::digits) - 1)];
            $count++;
        }
        return $ret;
    }
    
    static function generateRandomUppercaseAlphabets($num = 1)
    {        
        $count = 0;
        $ret = '';
        while ($count < $num) 
        {
            $ret .= self::alphUpper[mt_rand(0, count(self::alphUpper) - 1)];
            $count++;
        }
        
        return $ret;
    }
    
    static function generateRandomULowercaseAlphabets($num = 1)
    {   
        $count = 0;
        $ret = '';
        
        while ($count < $num) 
        {
            $ret .= self::alphLower[mt_rand(0, count(self::alphLower) - 1)];
            $count++;
        }
        
        return $ret;
    }
    
}