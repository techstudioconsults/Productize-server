<?php

namespace App\Enums;

enum RevenueActivity: string
{
    case SUBSCRIPTION = 'SUBSCRIPTION';
    case SUBSCRIPTION_RENEW = 'SUBSCRIPTION_RENEW';
    case PURCHASE = 'PURCHASE';
}
