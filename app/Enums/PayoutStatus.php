<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Completed = 'completed';
    case Pending = 'pending';
    case Failed = 'failed';
}
