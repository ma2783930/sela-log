<?php

namespace Sela\Models;

use Sela\Traits\HasFileName;
use Sela\Traits\HasISOTimestamp;
use Sela\Traits\HasUuid;

class DetailLog extends BaseModel
{
    use HasISOTimestamp;
    use HasFileName;
    use HasUuid;

    protected     $table            = 'sela_detail_logs';
    protected     $casts            = [
        'log_mime' => 'boolean',
        'value'    => 'json'
    ];
    public string $fileNameTemplate = '%/detaillog.json';
}
