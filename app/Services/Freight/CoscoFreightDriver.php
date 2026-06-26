<?php

namespace App\Services\Freight;

class CoscoFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('cosco');
    }
}
