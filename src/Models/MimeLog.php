<?php

namespace Sela\Models;

use Sela\Traits\HasFileName;
use Sela\Traits\HasISOTimestamp;
use Sela\Traits\HasUuid;

class MimeLog extends BaseModel
{
    use HasISOTimestamp, HasFileName, HasUuid;

    protected $table = 'sela_mime_logs';

    protected $fileNameTemplate = 'mimelog_%.json';
}
