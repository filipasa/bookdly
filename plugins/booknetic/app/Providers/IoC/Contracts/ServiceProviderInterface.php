<?php

namespace BookneticApp\Providers\IoC\Contracts;

use BookneticApp\Providers\IoC\ContainerBuilder;

interface ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void;
}
