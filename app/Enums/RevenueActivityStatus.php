<?php

namespace App\Enums;

enum RevenueActivityStatus: string
{
    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';
}
