<?php

namespace BookneticApp\Providers\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromRoute
{
    public string $name;
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
