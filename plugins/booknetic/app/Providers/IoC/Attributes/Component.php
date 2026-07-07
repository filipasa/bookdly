<?php

namespace BookneticApp\Providers\IoC\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Component
{
    public function __construct(
        public string $lifetime = 'singleton'
    ) {
    }
}
