<?php
namespace App\Core;

class Login
{
    private $who; 
    private $userData; 
    private $postData; 
    const loginAttemptLimit = 10;
    const loginAttemptTimeout = 10 * 60; // Time minutes
    
    function __construct() 
    {
        
    }
    
    function loginAdmin(\App\Models\Admin $adminObj, $post) 
    {
        $this->postData = $post;
        
        $this->userData = $adminObj
                ->where(USERNAME, '=', array_get($this->postData, USERNAME))
                ->first();
        
        if($this->userData && $this->userData[LEVEL] < 1) 
            return [SUCCESSFUL => FALSE, MESSAGE => 'Access Denied'];
                
        return $this->login();
    }
    
    private function login() 
    {
        $data = $this->isLoginValid();
        
        if(!$data) return [SUCCESSFUL => FALSE, MESSAGE => 'Enter correct username and password'];
        
        // If it is not activated, redirect the user to activation page
        if(!$data[ACTIVATED] && $this->who == 'user')
        {
            $token = \App\Core\JWT::encode([PHONE_NO => $data[PHONE_NO]], SECRET_KEY_JWT);
            
            return [
                SUCCESSFUL => false,
            
                'to_activate' => true,
                
                MESSAGE => 'Enter the activation code sent to your mobile',
                
                'activation_address' => getAddr('home_activate_user') . "?activation_token=$token",
            ];
        }
        
        // Remember Login
        $remLogin = null;
        if(array_get($this->postData, REMEMBER_LOGIN))
        {
            $loginDetails = [USERNAME => $this->postData[USERNAME], PASSWORD => $this->postData[PASSWORD], 'time' => time()];
            
            $remLogin = \App\Core\JWT::encode($loginDetails, SECRET_KEY_JWT);
            
            $data[REMEMBER_LOGIN] = $remLogin;
            
            $data->save();
        }
        
        
        return [	
            SUCCESSFUL => true, MESSAGE => 'Login successful',
            
            'data' => $data, REMEMBER_LOGIN => $remLogin,
            
            TOKEN => \App\Core\JWT::encode($data->toArray(), SECRET_KEY_JWT) 
        ];
        
    }
    
    private function isLoginValid()
    {
        if(!$this->userData) return null;
        
        $inputPassword = array_get($this->postData, PASSWORD);
        
        $time = time();
        
        if (    $this->userData[FAILED_LOGIN_COUNT] > self::loginAttemptLimit
            && ($time - $this->userData[FIRST_FAILED_LOGIN]) < self::loginAttemptTimeout)
        {
            // You are locked out
            return null;
        }
        
        if(!comparePasswords($this->userData[PASSWORD], $inputPassword))
        {
            // Wrong password
            if($this->userData[FAILED_LOGIN_COUNT] == 0)
            {
                $this->userData[FAILED_LOGIN_COUNT] = 1;
                $this->userData[FIRST_FAILED_LOGIN] = $time;
                $this->userData->save();
            }
            else
            {
                $this->userData[FAILED_LOGIN_COUNT] += 1;
                $this->userData->save();
            }
            return null;
        }
        
        if($this->userData[FAILED_LOGIN_COUNT] != 0)
        {
            $this->userData[FAILED_LOGIN_COUNT] = 0;
            $this->userData->save();
        }
        
        return $this->userData;
    }
    
}