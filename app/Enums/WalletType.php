<?php

namespace App\Enums;

enum WalletType: string
{
    case Credit = 'credit';
    case Debt = 'debt';
}