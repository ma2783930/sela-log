<?php

namespace Sela\Traits;

trait PathHelper
{
    /**
     * @param string $path
     * @return string
     */
    public function getFullPath(string $path): string
    {
        $basePath = sprintf('%/%', config('sela_log.path'), $path);

        if (config('sela_log.use_storage')) {
            return storage_path($basePath);
        }

        return $basePath;
    }
}
