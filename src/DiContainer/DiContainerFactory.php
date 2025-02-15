<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;

final class DiContainerFactory implements DiContainerFactoryInterface
{
    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     */
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
