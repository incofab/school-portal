<?php
namespace App\Core;

class CookieManager
{
    function __construct()
    {
        
    }
    
    function setLoginCookie($for, $data)
    {
        if($for == 'user')
        {
            setcookie(USER_REMEBER_LOGIN, $data, time() + (14 * 24 * 60 * 60), '/user'); // 2 weeks
        }
        elseif($for == 'admin')
        {
            setcookie(ADMIN_REMEBER_LOGIN, $data, time() + (14 * 24 * 60 * 60), '/manage/backend'); // 2 weeks
        }
    }
    
    function deleteLoginCookie($for, $data = null)
    {
        if($for == 'user')
        {
            setcookie(USER_REMEBER_LOGIN, $data, time() - 10000, '/user'); // 2 weeks
        }
        elseif($for == 'admin')
        {
            setcookie(ADMIN_REMEBER_LOGIN, $data, time() - 10000, '/manage/backend'); // 2 weeks
        }
    }
}