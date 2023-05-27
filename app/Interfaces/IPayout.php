<?php
namespace App\Interfaces;

use App\Models\User;

interface IPayout
{    
    public function getReference();
    
    public function persist();
    
    public function setStatus($status);
    
    public function setMessage($message);
    
    public function confirmPayout();
    
    
}

