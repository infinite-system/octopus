<?php

namespace X\Octopus;

use Illuminate\Database\Eloquent\Model as Base;
use X\Octopus\Concerns\TagRelations;


class Model extends Base
{
    use TagRelations;
}
