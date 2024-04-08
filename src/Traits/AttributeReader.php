<?php

namespace Sela\Traits;

use Illuminate\Http\Request;
use Sela\Attributes\SelaProcess;
use ReflectionClass;

trait AttributeReader
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return object|null
     * @throws \ReflectionException
     */
    public function getProcessAttribute(Request $request): ?SelaProcess
    {
        $controller = $request->route()?->controller;

        if (empty($controller)) {
            return null;
        }

        $method = $request->route()->getActionMethod();
        $reflection = new ReflectionClass(get_class($controller));
        $method = $reflection->getMethod($method);
        $attribute = $method->getAttributes(SelaProcess::class);

        if (!empty($attribute)) {
            return $attribute[0]->newInstance();
        }

        return null;
    }
}
