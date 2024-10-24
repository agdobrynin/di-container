<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

interface DiContainerCompilerInterface extends DiContainerInterface
{
    public function resource(array $resources): DiContainerCompilerInterface;

    public function exclude(array $excludes): DiContainerCompilerInterface;

    /**
     * @throws ContainerExceptionInterface
     */
    public function compile(): ContainerInterface;
}
