<?php

namespace App\Services\Freight;

class FlexportDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('flexport');
    }
}
