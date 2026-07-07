<?php

namespace BookneticApp\Providers\IoC\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Bind
{
    public function __construct(
        public string $interface
    ) {
    }
}
