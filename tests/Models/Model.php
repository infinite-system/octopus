<?php

namespace Tests\Models;
use X\Octopus\Model as Octopus;

class Model extends Octopus
{
    // overwrite model classes for tests
    protected string $tagModelClass = '\Tests\Models\ModalTag';
    protected string $modelModelClass = '\Tests\Models\Modal';

    public $timestamps = false;
}
