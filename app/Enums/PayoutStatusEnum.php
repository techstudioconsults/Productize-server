<?php

namespace App\Enums;

enum PayoutStatusEnum: string
{
    case Completed = 'completed';
    case Pending = 'pending';
    case Failed = 'failed';
}
