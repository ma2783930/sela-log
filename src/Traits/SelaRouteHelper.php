<?php

namespace Sela\Traits;

use Illuminate\Support\Facades\Config;

trait SelaRouteHelper
{
    /**
     * @param $route_name
     * @return array|null
     */
    public function getRouteSelaConfig($route_name): ?array
    {
        foreach (Config::get('sela_log.identifiers') as $section => $configurations) {
            foreach ($configurations as $index => $config) {
                if ($route_name == $config['route']) {
                    return [
                        'process_tag' => "{$section}_{$index}",
                        ...$config
                    ];
                }
            }
        }

        return null;
    }
}
