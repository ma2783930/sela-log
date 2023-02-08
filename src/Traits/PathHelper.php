<?php

namespace Sela\Traits;

use Storage;

trait PathHelper
{
    /**
     * @param string $path
     * @return string
     */
    public function getFullPath(string $path = ''): string
    {
        $basePath = str_replace(
            ['{configPath}', '{path}'],
            [config('sela.path'), $path],
            '{configPath}/{path}'
        );

        return Storage::disk('sela')->path($basePath);
    }
}
