<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Stripe = 'stripe';
    case MobileMoney = 'mobile_money';
}
