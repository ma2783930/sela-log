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
        $basePath = str_replace(
            ['{configPath}', '{path}'],
            [config('sela_log.path'), $path],
            '{configPath}/{path}'
        );

        if (config('sela_log.use_storage')) {
            return storage_path($basePath);
        }

        return $basePath;
    }
}
