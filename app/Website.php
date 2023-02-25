<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use X\Octopus\Model as Octopus;

class Website extends Octopus
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $casts = [
        "details" => "array"
    ];

    protected $fillable = [
        'id', 'name', 'domain', 'created_at', 'updated_at', 'deleted_at'
    ];
}
