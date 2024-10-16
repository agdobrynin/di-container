<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

interface ContainerCompilerInterface
{
    public function resource(array $resources): ContainerCompilerInterface;

    public function exclude(array $excludes): ContainerCompilerInterface;

    /**
     * @throws ContainerExceptionInterface
     */
    public function compile(): ContainerInterface;
}
