<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ProxyClosure implements DiAttributeInterface
{
    /**
     * @param class-string|non-empty-string $containerIdentifier class name or container identifier
     */
    public function __construct(private readonly string $containerIdentifier)
    {
        try {
            Helper::getContainerIdentifier($containerIdentifier, null);
        } catch (ContainerIdentifierExceptionInterface $e) {
            throw new AutowireAttributeException('The $id parameter must be a non-empty string.', previous: $e);
        }
    }

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->containerIdentifier;
    }
}
