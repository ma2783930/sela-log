<?php

namespace Sela\Traits;

use Str;

trait HasUuid
{
    public function initializeHasUuid(): void
    {
        $this->setIncrementing(false);
        $this->setKeyType('string');
    }

    public function bootHasUuid(): void
    {
        static::saving(function ($instance) {
            /** @var $instance self */
            $instance->id = Str::uuid();
        });
    }
}
