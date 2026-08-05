<?php

declare(strict_types=1);

namespace Modules\Chat\Enums;

enum ChatStatusEnum: string
{
    case ACTIVE = 'active';
    case BANNED = 'banned';
    case ARCHIVED = 'archived';
}
