<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;

class DiContainerFactory implements DiContainerFactoryInterface // @phan-suppress-current-line PhanUnreferencedClass
{
    public function make(iterable $definitions = []): DiContainer
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true,
            isSingletonServiceDefault: false,
        );

        return new DiContainer($definitions, $config);
    }
}
