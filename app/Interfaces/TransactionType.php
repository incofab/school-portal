<?php
namespace App\Interfaces;

use App\Models\User;

interface TransactionType
{    
    public function validateFields($post, User $user = null);
    
    public function onTransactionCreated($validatedData,
        \App\Models\Transaction $transaction,
        User $userData);
    
}

