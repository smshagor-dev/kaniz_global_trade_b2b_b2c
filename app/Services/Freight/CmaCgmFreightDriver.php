<?php

namespace App\Services\Freight;

class CmaCgmFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('cma_cgm');
    }
}
