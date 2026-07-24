<?php

namespace App\Helpers;

use Carbon\Carbon;

class DurationHelper
{
    public static function convertToHours($value, $unit)
    {
        // Agar duration nahi hai to default 1 hour
        if (empty($value) || empty($unit)) {
            return 1;
        }

        $start = Carbon::now();

        switch ($unit) {

            case 'minutes':
                return $value / 60;

            case 'hours':
                return $value;

            case 'days':
                return $start->copy()->addDays($value)->diffInHours($start);

            case 'weeks':
                return $start->copy()->addWeeks($value)->diffInHours($start);

            case 'months':
                return $start->copy()->addMonths($value)->diffInHours($start);

            case 'years':
                return $start->copy()->addYears($value)->diffInHours($start);

            default:
                return 1;
        }
    }
}