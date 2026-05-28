<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use UnitEnum;

final class DiDefinitionParameter extends DiDefinitionParameterWithContextAbstract implements DiDefinitionNoArgumentsInterface, DiDefinitionParameterInterface, ResetInterface
{
    public function __construct(private readonly string $name = '') {}

    public function getDefinition(): string
    {
        return $this->name;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null
    {
        return $container->parameters()
            ->get($this->nameWithContext($context))
        ;
    }
}
