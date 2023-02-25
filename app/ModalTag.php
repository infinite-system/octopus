<?php

namespace App;

use X\Octopus\Model as Octopus;

class ModalTag extends Octopus
{
    const status = ['active' => 1, 'in_active' => 0, 'hidden' => 8, 'deleted' => 9];

    protected $fillable = ['updated_at', 'source_type', 'source_id', 'target_type', 'target_id', 'status'];

    protected $table = 'tags';

    function setCreatedAt($value) { }
}
