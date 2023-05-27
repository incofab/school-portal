<?php
namespace App\Core\Password;

class PasswordReset
{
    private $passwordResetModel;
    private $userModel;
    private $codeGenerator;
    private $SMSHandle;
    
    function __construct(
        \App\Models\PasswordReset $passwordResetModel,
        \App\Models\Users $userModel, 
        \App\Core\CodeGenerator $codeGenerator, 
        \App\Core\SMSHandler $SMSHandle) 
    {
        $this->passwordResetModel = $passwordResetModel;
        $this->userModel = $userModel;
        $this->codeGenerator = $codeGenerator;
        $this->SMSHandle = $SMSHandle;
    }
    
    function resetPassword($post)
    {
        if(!isset($post['reset_code']) || !isset($post[PASSWORD]) || !isset($post[PASSWORD_CONFIRMATION]))
        {
            return [SUCCESSFUL => false, MESSAGE => 'Fill all fields'];
        }
        
        $resetData = $this->passwordResetModel->where(PASSWORD_RESET_CODE, '=', $post['reset_code'])
                    ->where(IS_STILL_VALID, '=', true)->first();
        
        if(!$resetData) return [SUCCESSFUL => false, MESSAGE => 'Invalid Reset code'];
        
        if(time() > $resetData[EXPIRY_TIME]) return [SUCCESSFUL => false, MESSAGE => 'Reset code expired'];
        
        $newpassword = $post[PASSWORD];
        $cpassword = $post[PASSWORD_CONFIRMATION];
        
        if($newpassword !== $cpassword) return [SUCCESSFUL => false, MESSAGE => 'Error: Password mismatch'];
        
        $userData = $resetData->user()->first();
        
        $userData[PASSWORD] = cryptPassword($newpassword);
        
        $success = $userData->save();
        
        if($success)
        {
            // Nulify password reset table row
            $resetData[IS_STILL_VALID] = false;
            
            $resetData->save();
            
            return [SUCCESSFUL => true, MESSAGE => 'Password changed successfully'];
        }
        
        return [SUCCESSFUL => false, MESSAGE => 'Password change failed'];
    }
    
    function sendResetCode($post)
    {
        if(!isset($post['forgot_password']))
        {
            return [SUCCESSFUL => false, MESSAGE => 'Fill all fields'];
        }
        
        $phoneTrimmed = '234' . ltrim($post['forgot_password'], 0);
        $phoneTrimmedPlus = '+' . $phoneTrimmed;
        $data = $this->userModel->where(PHONE_NO, '=', $post['forgot_password'])
                ->orWhere(PHONE_NO, '=', $phoneTrimmed)
                ->orWhere(PHONE_NO, '=', $phoneTrimmedPlus)
                ->first([PHONE_NO]);
        
        if(!$data) return [SUCCESSFUL => false, MESSAGE => 'The supplied credentials does not exist'];
        
        $time = time();
        
        if($this->passwordResetModel->where(PHONE_NO, '=', $data[PHONE_NO])
            ->where(EXPIRY_TIME, '>', $time)
            ->where(IS_STILL_VALID, '=', true)->first())
        {
            return [SUCCESSFUL => false, MESSAGE => 'Wait for at least 15 minutes before requesting another one.'];
        }
        
        $resetCode = $this->codeGenerator->generateRandomDigits(9);
        
        // Ensure that the reset code is unique.
        while($this->passwordResetModel->where(PASSWORD_RESET_CODE, '=', $resetCode)->first())
        {
            $resetCode = $this->codeGenerator->generateRandomDigits(9);
        }
        
        $to = $data[PHONE_NO];
        
        $msg = "Password reset code: $resetCode. " .
            'Note that this code expires after 15mins. Follow this address to reset your password ' .
            
        $_SERVER['SERVER_NAME'] . getAddr('reset_password');
        
        $ret =  $this->SMSHandle->sendSMS($to, $msg); 
        
        if($ret[SUCCESS])
        {
            $this->passwordResetModel->create([
                
                PHONE_NO => $data[PHONE_NO],
                
                PASSWORD_RESET_CODE => $resetCode,
                
                EXPIRY_TIME => $time * 15 * 60,	// Code expires after 15 minutes
            ]);
        }
        
        return $ret;
    }
    
    
}