<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerFactory implements DiContainerFactoryInterface
{
    public function make(
        iterable $definitions = [],
        string $referenceContainerSymbol = '@',
    ): DiContainerInterface {
        $config = new DiContainerConfig(
            referenceContainerSymbol: $referenceContainerSymbol,
            useAutowire: true,
            useZeroConfigurationDefinition: true,
            useAttribute: true,
            isSharedServiceDefault: false
        );

        return new DiContainer(
            definitions: $definitions,
            config: $config,
        );
    }
}
