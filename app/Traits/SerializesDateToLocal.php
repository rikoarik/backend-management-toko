<?php

namespace App\Traits;

use DateTimeInterface;

trait SerializesDateToLocal
{
    /**
     * Prepare a date for array / JSON serialization.
     * Output in application timezone instead of UTC.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->setTimezone(new \DateTimeZone(config('app.timezone')))
            ->format('Y-m-d H:i:s');
    }
}
