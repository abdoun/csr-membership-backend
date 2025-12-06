<?php

namespace App\Enum;

enum UserLevel: string
{
    case BASIC = 'basic';
    case ADVANCED = 'advanced';
    case ADMIN = 'admin';
}
