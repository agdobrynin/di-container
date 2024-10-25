<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;

interface DiContainerCompilerInterface extends DiContainerInterface
{
    public function resource(iterable $resources): DiContainerCompilerInterface;

    public function exclude(iterable $excludes): DiContainerCompilerInterface;

    /**
     * @throws ContainerExceptionInterface
     */
    public function compile(): DiContainerCallInterface&DiContainerInterface;
}
