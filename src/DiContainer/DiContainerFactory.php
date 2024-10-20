<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerInterface;

class DiContainerFactory implements DiContainerFactoryInterface // @phan-suppress-current-line PhanUnreferencedClass
{
    public function make(iterable $definitions = []): DiContainerInterface
    {
        $config = new DiContainerConfig(
            useAutowire: true,
            useZeroConfigurationDefinition: true,
            useAttribute: true,
            isSingletonServiceDefault: false,
            referenceContainerSymbol: '@'
        );

        return ($c = new DiContainer($definitions, $config))->set(ContainerInterface::class, $c);
    }
}
