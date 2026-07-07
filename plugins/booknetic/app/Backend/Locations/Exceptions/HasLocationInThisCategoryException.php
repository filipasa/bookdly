<?php

namespace BookneticApp\Backend\Locations\Exceptions;

use Exception;

class HasLocationInThisCategoryException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please remove the locations from this category first.'));
    }
}
