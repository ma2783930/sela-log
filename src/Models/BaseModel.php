<?php

namespace Sela\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public    $timestamps   = false;
    protected $guarded      = [];
    protected $casts        = [];
}
