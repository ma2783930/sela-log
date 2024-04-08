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
        $action = $request->route()?->action;
        $routeController = $action['controller'] ?? null;        

        if (empty($routeController)) {
            return null;
        }

        list($controller, $method) = explode('@', $routeController);

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod($method);
        $attribute = $method->getAttributes(SelaProcess::class);

        if (!empty($attribute)) {
            return $attribute[0]->newInstance();
        }

        return null;
    }
}
