<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TagDefaultPriorityMethod implements DiAttributeInterface
{
    public function __construct(private string $defaultPriorityMethod) {}

    public function getIdentifier(): string
    {
        return $this->defaultPriorityMethod;
    }
}
