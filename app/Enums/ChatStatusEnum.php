<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatStatusEnum: string
{
    case ACTIVE = 'active';
    case BANNED = 'banned';
    case ARCHIVED = 'archived';
}
