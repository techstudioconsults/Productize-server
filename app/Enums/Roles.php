<?php

namespace App\Enums;

enum Roles: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case USER = 'USER';
}
