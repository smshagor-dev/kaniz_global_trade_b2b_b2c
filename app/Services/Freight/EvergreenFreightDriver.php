<?php

namespace App\Services\Freight;

class EvergreenFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('evergreen');
    }
}
