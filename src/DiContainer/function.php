<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

// @phan-suppress-next-line PhanUnreferencedFunction
function diDefinition(?string $containerKey = null, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): array
{
    $prepareDefinition = (
        ($definition ? [0 => $definition] : [])
        + ($arguments ? [DiContainerInterface::ARGUMENTS => $arguments] : [])
        + (null !== $isSingleton ? [DiContainerInterface::SINGLETON => $isSingleton] : [])
    ) ?: throw new ContainerException('Definition function is empty.');

    return $containerKey ? [$containerKey => $prepareDefinition] : $prepareDefinition;
}
