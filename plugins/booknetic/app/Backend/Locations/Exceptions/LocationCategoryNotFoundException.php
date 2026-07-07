<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;

class LocationCategoryNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Location category not found!'));
    }
}
