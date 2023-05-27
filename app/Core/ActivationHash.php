<?php
namespace App\Core;

class ActivationHash
{
    function __construct()
    {
        
    }
    
    function generateActivationHash($productKey) 
    {
        if(empty($productKey)) return null;
        
        $productKey = trim($productKey);
        $productKey = str_replace(' ', '', $productKey);
        $productKey = strtoupper($productKey);
        
        $bytesArr = $this->getBytes($productKey);
        
        $sb = '';
        
        $sum = $this->getSum($bytesArr);
        $count = count($bytesArr);
        
        for ($i = 0; $i < $count; $i++) 
        {    
            $val = $bytesArr[$i]*31;
            $val += ($sum * $i);
            
            $hex = strtoupper(dechex($val));
            
            $hexCount = strlen($hex);
            
            if ($hexCount == 1) 
            {
                $sb .= '0'.$hex[($hexCount-1)];
            } 
            else 
            {
                $sb .= substr($hex, ($hexCount - 2));
            }
        }
        
        $md5 = md5($sb);
        $digitsOnly = $this->replaceLetters($md5);
        return substr($digitsOnly, 10, 12);
    }
    
    private function getBytes($string) 
    {
        $bytes = array();
        
        for($i = 0; $i < strlen($string); $i++)
        {
            $bytes[] = $this->uniord($string[$i]);
        }
        
        return $bytes;
    }
    
    private function getSum($bytesArr)
    {
        $sum = 0;
        $len = count($bytesArr);
        for ($i = 0; $i < $len; $i++) 
        {
            $sum += $bytesArr[$i];
        }
        
        return $sum;
    }
    
    private function replaceLetters($str)
    {
        $str = strtolower($str);
        $search  = ['a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', '0'];
        $replace = ['3', '3', '4', '4', '5', '5', '6', '6', '7', '7', '8', '8', '9'];
        $str = str_replace($search, $replace, $str);
        return $str;
    }
    
    
    /**
     * Convert unicode character back to string
     */
    private function _unichr($o) 
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding('&#'.intval($o).';', 'UTF-8', 'HTML-ENTITIES');
        } else {
            return chr(intval($o));
        }
    }
    
    /**
     * Convert to unicode character
     */
    private function uniord($c) 
    {
        if (ord($c[0]) >=0 && ord($c[0]) <= 127)
            return ord($c[0]);
        if (ord($c[0]) >= 192 && ord($c[0]) <= 223)
            return (ord($c[0])-192)*64 + (ord($c[1])-128);
        if (ord($c[0]) >= 224 && ord($c[0]) <= 239)
            return (ord($c[0])-224)*4096 + (ord($c[1])-128)*64 + (ord($c[2])-128);
        if (ord($c[0]) >= 240 && ord($c[0]) <= 247)
            return (ord($c[0])-240)*262144 + (ord($c[1])-128)*4096 + (ord($c[2])-128)*64 + (ord($c[3])-128);
        if (ord($c[0]) >= 248 && ord($c[0]) <= 251)
            return (ord($c[0])-248)*16777216 + (ord($c[1])-128)*262144 + (ord($c[2])-128)*4096 + (ord($c[3])-128)*64 + (ord($c[4])-128);
        if (ord($c[0]) >= 252 && ord($c[0]) <= 253)
            return (ord($c[0])-252)*1073741824 + (ord($c[1])-128)*16777216 + (ord($c[2])-128)*262144 + (ord($c[3])-128)*4096 + (ord($c[4])-128)*64 + (ord($c[5])-128);
        if (ord($c[0]) >= 254 && ord($c[0]) <= 255)    //  error
            return FALSE;
        return 0;
    } 
}