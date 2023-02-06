<?php

namespace Sela\Traits;

use Str;

trait HasUuid
{
    public function initializeHasUuid()
    {
        $this->setIncrementing(false);
        $this->setKeyType('string');
    }

    public static function bootHasUuid()
    {
        static::saving(function ($instance) {
            /** @var $instance self */
            $instance->id = Str::uuid();
        });
    }
}
