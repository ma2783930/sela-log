<?php

namespace Sela\Models;

use Sela\Traits\HasFileName;
use Sela\Traits\HasISOTimestamp;
use Sela\Traits\HasUuid;

class MimeLog extends BaseModel
{
    use HasISOTimestamp;
    use HasFileName;
    use HasUuid;

    protected $table        = 'sela_mime_logs';
    public string $fileNameTemplate = '%/mimelog.json';
}
