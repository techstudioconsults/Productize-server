<?php

namespace App\Enums;

enum Roles: string
{
    case SUPER_ADMIN = 'SUPER ADMIN';
    case ADMIN = 'ADMIN';
    case USER = 'USER';
}
