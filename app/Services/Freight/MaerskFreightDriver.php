<?php

namespace App\Services\Freight;

class MaerskFreightDriver extends GenericFreightDriver
{
    public function __construct()
    {
        parent::__construct('maersk');
    }
}
