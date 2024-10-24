<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerCompilerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Psr\Container\ContainerInterface;

class DiContainerCompiler extends DiContainer implements DiContainerCompilerInterface
{
    public function __construct(protected string $compiledContainerFile, iterable $definitions = [], ?DiContainerConfigInterface $config = null)
    {
        parent::__construct($definitions, $config);
    }

    public function resource(iterable $resources): DiContainerCompilerInterface
    {
        return $this;
    }

    public function exclude(iterable $excludes): DiContainerCompilerInterface
    {
        return $this;
    }

    public function compile(): ContainerInterface
    {
        throw new \LogicException('Compiling is not implemented');
    }
}
