<?php

namespace BookneticAddon\Customforms\Model;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\Translation\Translator;

class FormInputChoice extends Model
{
    use Translator;

    protected static $translations = [ 'title' ];
}