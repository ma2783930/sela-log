<?php

namespace Sela\Traits;

use Str;

trait HasUuid
{
    public $incrementing = false;
    public $keyType      = 'string';

    public function bootHasUuid(): void
    {
        static::saving(function ($instance) {
            /** @var $instance self */
            $instance->id = Str::uuid();
        });
    }
}
