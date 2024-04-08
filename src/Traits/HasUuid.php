<?php

namespace Sela\Traits;

use Illuminate\Support\Str;

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
            $instance->id = Str::uuid();
        });
    }
}
