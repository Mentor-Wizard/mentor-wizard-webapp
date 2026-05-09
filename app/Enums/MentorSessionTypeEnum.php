<?php

declare(strict_types=1);

namespace App\Enums;

enum MentorSessionTypeEnum: string
{
    case VIDEO_SESSION = 'Video Session';
    case VOICE_SESSION = 'Voice Session';
    case CODE_REVIEW = 'Code Review';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
