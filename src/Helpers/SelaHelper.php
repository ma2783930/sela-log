<?php

namespace Sela\Helpers;

use Illuminate\Http\Request;
use Sela\Attributes\SelaProcess;
use Sela\Jobs\UpdateSelaLogFiles;
use Sela\Traits\HasDatabaseLog;
use ReflectionClass;
use Exception;

class SelaHelper
{
    use HasDatabaseLog;

    /**
     * @return array[]
     */
    public function getPaginationDataTags(): array
    {
        return [
            ['name' => 'quick_filter', 'info' => 'Quick filter text'],
            ['name' => 'page', 'info' => 'Requested page number'],
            ['name' => 'per_page', 'info' => 'Requested page size'],
            ['name' => 'sort_order', 'info' => 'Requested sort order'],
            ['name' => 'sort_field', 'info' => 'Requested sort field'],
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param array                    $values
     * @return void
     */
    public function insertLog(Request $request, array $values): void
    {
        try {
            $controller = $request->route()->controller;
            $method     = $request->route()->getActionMethod();
            $reflection = new ReflectionClass(get_class($controller));
            $method     = $reflection->getMethod($method);
            $attribute  = $method->getAttributes(SelaProcess::class);

            if (!empty($attribute)) {
                /** @var $attributeClass SelaProcess */
                $attributeClass = $attribute[0]->newInstance();

                foreach ($attributeClass->data_tags as $tag) {
                    if (!isset($values[$tag['name']])) {
                        throw new Exception('All of data tags must be present.');
                    }
                }

                $action = $this->insertActionLog($attributeClass->process_name);

                foreach ($attributeClass->data_tags as $data_tag) {
                    $this->insertDetailLog($action, $data_tag['name'], $values[$data_tag['name']], $data_tag['log_mime'] ?? false);
                }

                UpdateSelaLogFiles::dispatch();
            }
        } catch (Exception $exception) {
            //
        }
    }
}
