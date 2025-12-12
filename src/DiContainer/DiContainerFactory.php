<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;

final class DiContainerFactory implements DiContainerFactoryInterface
{
    public function __construct(private readonly ?DiContainerConfigInterface $config = null) {}

    public function make(iterable $definitions = []): DiContainer
    {
        $config = $this->config ?? new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true,
            isSingletonServiceDefault: false,
        );

        return new DiContainer($definitions, $config);
    }
}
