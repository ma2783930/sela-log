<?php

namespace Sela\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Sela\Traits\HasFileName;
use Sela\Traits\HasISOTimestamp;
use Sela\Traits\HasUuid;

class ActionLog extends BaseModel
{
    use HasISOTimestamp, HasFileName, HasUuid;

    protected $table = 'sela_action_logs';

    protected $fileNameTemplate = 'actionlog_%.json';

    public function details(): HasMany
    {
        return $this->hasMany(DetailLog::class, 'actionlog_id');
    }

    public function mimes(): HasMany
    {
        return $this->hasMany(MimeLog::class, 'actionlog_id');
    }
}
