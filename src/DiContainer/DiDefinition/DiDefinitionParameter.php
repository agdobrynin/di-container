<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterInterface;
use UnitEnum;

final class DiDefinitionParameter implements DiDefinitionParameterInterface
{
    public function __construct(private readonly string $name) {}

    public function getDefinition(): string
    {
        return $this->name;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null
    {
        if ('' === $this->name) {
            throw new DiDefinitionException('Parameter name must be non-empty string.');
        }

        return $container->parameters()->get($this->name);
    }
}
