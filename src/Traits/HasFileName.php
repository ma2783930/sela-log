<?php

namespace Sela\Traits;

trait HasFileName
{
    public static function bootHasFileName(): void
    {
        static::saving(function ($instance) {
            /** @var $instance self */
            $instance->file_name = str_replace('%', verta()->format('Y_m_d'), $instance->fileNameTemplate);
        });
    }
}
