<?php

namespace Sela\Attributes;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_METHOD)]
class SelaProcess
{
    public string $process_name;
    public string $info;
    public array $data_tags = [];
    public bool $auto_log;

    /**
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        //$this->validateConfiguration($config);
        $data_tags = $config['data_tags'] ?? [];
        if (isset($config['pagination']) && $config['pagination']) {
            $data_tags = [
                ...$data_tags,
                ['name' => 'quick_filter', 'info' => 'Quick filter text'],
                ['name' => 'page', 'info' => 'Requested page number'],
                ['name' => 'per_page', 'info' => 'Requested page size'],
                ['name' => 'sort_order', 'info' => 'Requested sort order'],
                ['name' => 'sort_field', 'info' => 'Requested sort field'],
            ];
        }

        $this->process_name = $config['name'];
        $this->info = $config['info'];
        $this->data_tags = $data_tags;
        $this->auto_log = $config['auto_log'] ?? true;
    }

    /**
     * @throws Exception
     */
    private function validateConfiguration($config): void
    {
        if (!isset($config['name'])) {
            throw new Exception('Invalid configuration, `name` key does not exists.');
        }

        if (!isset($config['info'])) {
            throw new Exception('Invalid configuration, `info` key does not exists.');
        }

        if (isset($tag['auto_log']) && !is_bool($tag['auto_log'])) {
            throw new Exception('auto_log key type must be boolean.');
        }

        if (!isset($config['data_tags'])) {
            throw new Exception('Invalid configuration, `data_tags` key does not exists.');
        }

        if (!is_array($config['data_tags'])) {
            throw new Exception('Invalid configuration, `data_tags` key type must be array.');
        }

        foreach ($config['data_tags'] as $tag) {

            if (!isset($tag['name'])) {
                throw new Exception('Missing `name` key in data tag.');
            }

            if (!isset($tag['info'])) {
                throw new Exception('Missing `info` key in data tag.');
            }

            if (isset($tag['RType']) && $tag['RType'] != 'L') {
                throw new Exception('RType value must be equal to `L`');
            }

            if (isset($tag['log_mime']) && !is_bool($tag['log_mime'])) {
                throw new Exception('log_mime key type must be boolean.');
            }
        }
    }
}
