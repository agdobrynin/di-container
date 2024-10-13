<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerFactory implements DiContainerFactoryInterface
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

        return new DiContainer(
            definitions: $definitions,
            config: $config,
        );
    }
}
