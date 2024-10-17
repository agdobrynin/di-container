<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

// @phan-suppress-next-line PhanUnreferencedFunction
function diDefinition(mixed $definition = null, array $arguments = [], ?bool $isSingleton = null): array
{
    return ($definition ? [0 => $definition] : [])
        + ($arguments ? [DiContainerInterface::ARGUMENTS => $arguments] : [])
        + (null !== $isSingleton ? [DiContainerInterface::SINGLETON => $isSingleton] : []);
}
