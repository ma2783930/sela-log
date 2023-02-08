<?php

namespace Sela\Traits;

trait PathHelper
{
    /**
     * @param string $path
     * @return string
     */
    public function getFullPath(string $path = ''): string
    {
        return str_replace(
            ['{configPath}', '{path}'],
            [config('sela.path'), $path],
            '{configPath}/{path}'
        );
    }
}
