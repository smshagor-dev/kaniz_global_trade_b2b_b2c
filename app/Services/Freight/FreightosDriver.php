<?php

namespace App\Services\Freight;

class FreightosDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('freightos');
    }
}
