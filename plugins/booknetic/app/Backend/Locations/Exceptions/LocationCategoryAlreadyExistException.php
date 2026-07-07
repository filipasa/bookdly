<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;

class LocationCategoryAlreadyExistException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('This category already exists!'));
    }
}
