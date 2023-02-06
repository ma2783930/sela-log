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

    protected $table        = 'sela_detail_logs';
    public string $fileNameTemplate = 'detaillog_%.json';
}
