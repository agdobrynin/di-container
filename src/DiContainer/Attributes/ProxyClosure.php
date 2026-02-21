<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ProxyClosure
{
    /**
     * @param class-string|non-empty-string $id class name or container identifier
     */
    public function __construct(public readonly string $id)
    {
        try {
            Helper::getContainerIdentifier($id, null);
        } catch (ContainerIdentifierExceptionInterface $e) {
            throw new AutowireAttributeException('The $id parameter must be a non-empty string.', previous: $e);
        }
    }
}
