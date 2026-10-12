<?php

namespace App\Enums;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case Investment = 'investment';
}
