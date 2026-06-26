<?php

namespace App\Services\Freight;

class HapagLloydFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('hapag_lloyd');
    }
}
