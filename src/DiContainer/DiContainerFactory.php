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
            autowire: new Autowired(useAttribute: true),
            linkContainerSymbol: $linkContainerSymbol,
            delimiterAccessArrayNotationSymbol: $delimiterAccessArrayNotationSymbol,
            useZeroConfigurationDefinition: true,
            isSharedServiceDefault: false,
            useAttribute: true
        );

        return new DiContainer(
            definitions: $definitions,
            config: $config,
        );
    }
}
