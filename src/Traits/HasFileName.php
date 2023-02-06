<?php

namespace Sela\Traits;

use Carbon\Carbon;

trait HasFileName
{
    public static function bootHasFileName(): void
    {
        static::saving(function ($instance) {
            /** @var $instance self */
            $instance->file_name = sprintf($instance->fileNameTemplate, Carbon::now()->format('Y_m_d'));
        });
    }
}
