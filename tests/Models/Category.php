<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Category extends Model {

    use SoftDeletes;

    protected $table = 'category';
}
