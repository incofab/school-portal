<?php
namespace App\Core\Password;

class PasswordChange
{
    private $jwt;
    private $sessionKey;
    
    function __construct(\App\Core\JWT $jwt) 
    {
        $this->jwt = $jwt;
    }
    
    function changeUserPassword($post, \App\Models\Users $userData)
    {
        $this->sessionKey = USER_SESSION_DATA;
        
        return $this->changePassword($post, $userData);
    }
    
    function changeAdminPassword($post, \App\Models\Admin $adminData)
    {
        $this->sessionKey = ADMIN_SESSION_DATA;
        
        return $this->changePassword($post, $adminData);
    }
    
    private function changePassword($post, $userData)
    {
        if (!isset($post['new_password']) || !isset($post[PASSWORD])
            || !isset($post[PASSWORD_CONFIRMATION]))
        {
            return [SUCCESS => false, MESSAGE => 'Fill all fields'];
        }
        
        $oldpassword = $post[PASSWORD];
        $newpassword = $post['new_password'];
        $cpassword = $post[PASSWORD_CONFIRMATION];
        
        if($newpassword !== $cpassword)
        {
            return [SUCCESS => false, MESSAGE => 'New password must match confirmation password'];
        }
        
        if(!comparePasswords($userData[PASSWORD], $oldpassword))
        {
            return [SUCCESS => false, MESSAGE => 'Incorrect password'];
        }
                
        $userData[PASSWORD] = cryptPassword($newpassword);
        
        if($userData->save())
        {
            return [
                SUCCESS => true, MESSAGE => 'Password changed successfully', 
                'data' => $userData->toArray(),
                TOKEN => $this->jwt->encode($userData, SECRET_KEY_JWT)
            ];
        }
            
        return [SUCCESS => false, MESSAGE => 'Error: Password change failed'];
    }
    
}