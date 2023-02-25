<?php

namespace Tests\Models;



class ModalTag extends Model
{
    const status = ['active' => 1, 'in_active' => 0, 'hidden' => 8, 'deleted' => 9];

    protected $fillable = ['source_type', 'source_id', 'target_type', 'target_id', 'thru_type', 'thru_id', 'status'];

    protected $table = 'tags';
}
