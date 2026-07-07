<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;

class NoCategorySelectedException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please select a category.'));
    }
}
