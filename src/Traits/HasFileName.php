<?php

namespace Sela\Traits;

trait HasFileName
{
    public static function bootHasFileName(): void
    {
        static::saving(function ($instance) {            
            $dateFormat = config('sela.path_date_format', 'Y_m_d');
            $instance->file_name = str_replace('%', verta()->format($dateFormat), $instance->fileNameTemplate);
        });
    }
}
