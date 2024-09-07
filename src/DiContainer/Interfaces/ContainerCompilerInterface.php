<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

interface ContainerCompilerInterface
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function build(): ContainerInterface;
}
