<?php

namespace Sela\Models;

use Illuminate\Database\Eloquent\Model;

class SelaMimeLog extends Model
{
    protected $table      = 'sela_mime_logs';
    public    $timestamps = false;
    protected $guarded    = [];
    protected $casts      = [];
}
