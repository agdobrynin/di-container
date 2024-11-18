<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ServiceByReference implements DiAttributeInterface
{
    /**
     * @param non-empty-string $id reference to container identifier
     */
    public function __construct(private string $id)
    {
        if ('' === \trim($id)) {
            throw new AutowiredAttributeException('Attribute #['.__CLASS__.'] argument [id] must be a non-empty string.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }
}