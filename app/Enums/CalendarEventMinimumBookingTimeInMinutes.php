<?php

namespace App\Enums;

enum CalendarEventMinimumBookingTimeInMinutes: int
{
    case HALF_HOUR = 30;
    case HOUR = 60;
    case TWO_HOURS = 120;
    case SIX_HOURS = 360;
    case TWELVE_HOURS = 720;
    case DAY = 1440;
    case TWO_DAYS = 2880;
    case WEEK = 10080;
}
