<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ServiceByReference implements DiAttributeInterface
{
    /**
     * @param non-empty-string $id reference to container identifier
     */
    public function __construct(private string $id) {}

    public function getIdentifier(): string
    {
        return $this->id;
    }
}
