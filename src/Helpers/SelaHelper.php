<?php

namespace Sela\Helpers;

use Illuminate\Http\Request;
use Sela\Traits\HasDatabaseLog;

class SelaHelper
{
    use HasDatabaseLog;

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

    public function insertLog(Request $request, $processName, $dataTags = []): void
    {
        $action = $this->insertActionLog($processName);

        foreach ($dataTags as $data_tag) {
            if ($request->has($data_tag['name'])) {
                $this->insertDetailLog($action, $data_tag['name'], $request->{$data_tag['name']}, $data_tag['log_mime'] ?? false);
            } else if (!empty($value = $request->route()->originalParameter(str($data_tag['name'])->rtrim('_id')->camel()->toString()))) {
                $this->insertDetailLog($action, $data_tag['name'], $value);
            }
        }
    }
}
