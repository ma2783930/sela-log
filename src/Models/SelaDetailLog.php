<?php

namespace Sela\Models;

use Illuminate\Database\Eloquent\Model;

class SelaDetailLog extends Model
{
    protected $table        = 'sela_detail_logs';
    public    $incrementing = false;
    public    $keyType      = 'string';
    public    $timestamps   = false;
    protected $guarded      = [];
    protected $casts        = [];
}
