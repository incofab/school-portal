<?php
namespace App\Core;

class EmailSender
{
    
    function sendTextEmail($to, $subject, $message) 
    {
        $ret = @mail($to, $subject, $message, $headers);
        
        return $ret;
    }
    
    function sendHTMLEmail($to, $from, $subject, $message) 
    {
        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        
        // Create email headers
        $headers .= 'From: '.$from."\r\n".
            'Reply-To: '.$from."\r\n" .
            'X-Mailer: PHP/' . phpversion();
        
        $ret = @mail($to, $subject, $message, $headers);
        
        return $ret;
    }
    
}