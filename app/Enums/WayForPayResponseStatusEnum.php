<?php

declare(strict_types=1);

namespace App\Enums;

enum WayForPayResponseStatusEnum: string
{
    case SUCCESS = 'success';
    case CREATED = 'Created';
}
