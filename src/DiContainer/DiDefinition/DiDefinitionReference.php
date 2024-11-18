<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DiDefinitionReference implements DiDefinitionInterface
{
    public function __construct(private string $reference) {}

    public function getDefinition(): string
    {
        return $this->reference;
    }
}
