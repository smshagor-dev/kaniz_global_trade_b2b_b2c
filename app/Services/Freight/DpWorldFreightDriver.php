<?php

namespace App\Services\Freight;

class DpWorldFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('dp_world');
    }
}
