<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class AsClosure implements DiAttributeInterface
{
    /**
     * @param class-string|non-empty-string $id class name or container identifier
     */
    public function __construct(private string $id)
    {
        if ('' === \trim($id)) {
            throw new AutowireAttributeException('Attribute #['.__CLASS__.'] argument [id] must be a non-empty string.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }
}
