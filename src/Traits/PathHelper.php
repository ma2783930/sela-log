<?php

namespace Sela\Traits;

trait PathHelper
{
    public function getFullPath($path)
    {
        if (config('sela_log.use_storage')) {
            return storage_path($path);
        }

        return $path;
    }
}
