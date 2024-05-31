<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'active';
    case NON_RENEWING = 'non-renewing';
    case ATTENTION = 'attention';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';
}
