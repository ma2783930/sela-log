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
            [config('sela.path'), $path],
            '{configPath}/{path}'
        );

        if (config('sela.use_storage')) {
            return storage_path($basePath);
        }

        return $basePath;
    }
}
