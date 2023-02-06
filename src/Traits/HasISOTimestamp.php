<?php

namespace Sela\Traits;

use Carbon\Carbon;

trait HasISOTimestamp
{
    public static function bootHasISOTimestamp(): void
    {
        static::saving(function ($instance) {
            /** @var $instance self */
            $instance->timestamp = Carbon::now()->toISOString(true);
        });
    }
}
