<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Website extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $casts = [
        "details" => "array"
    ];

    protected $fillable = [
        'id', 'name'
    ];
}
