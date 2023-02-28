<?php

namespace Sela\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getPaginationDataTags()
 * @method static void insertLog(Request $request, $dataTags = [])
 */
class Sela extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sela';
    }
}
