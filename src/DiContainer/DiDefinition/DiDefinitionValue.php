<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;

final class DiDefinitionValue implements DiDefinitionInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
{
    use TagsTrait;

    public function __construct(private readonly mixed $definition) {}

    public function getDefinition(): mixed
    {
        return $this->definition;
    }

    /**
     * @throws void
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        return $this->definition;
    }
}
