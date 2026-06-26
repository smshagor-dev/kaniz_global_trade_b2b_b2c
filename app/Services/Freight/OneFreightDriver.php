<?php

namespace App\Services\Freight;

class OneFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('one');
    }
}
