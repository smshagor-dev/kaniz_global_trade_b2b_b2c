<?php

namespace App\Services\Freight;

class MscFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('msc');
    }
}
