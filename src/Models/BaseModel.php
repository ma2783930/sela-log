<?php

namespace Sela\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{    
    protected $guarded = [];
    protected $casts = [];

    public $timestamps = false;
}
