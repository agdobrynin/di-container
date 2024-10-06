<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerFactory implements DiContainerFactoryInterface
{
    public function make(
        iterable $definitions = [],
        string $linkContainerSymbol = '@',
        string $delimiterAccessArrayNotationSymbol = '.'
    ): DiContainerInterface {
        $config = new DiContainerConfig(
            linkContainerSymbol: $linkContainerSymbol,
            delimiterAccessArrayNotationSymbol: $delimiterAccessArrayNotationSymbol,
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
