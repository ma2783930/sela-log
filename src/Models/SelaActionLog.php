<?php

namespace Sela\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sela\Models\SelaDetailLog;

class SelaActionLog extends Model
{
    protected $guarded = [];
    protected $casts   = [];

    public function selaDetailLogs(): HasMany
    {
        return $this->hasMany(SelaDetailLog::class, 'actionlog_id');
    }

    public function selaMimeLogs(): HasMany
    {
        return $this->hasMany(SelaMimeLog::class, 'actionlog_id');
    }
}
