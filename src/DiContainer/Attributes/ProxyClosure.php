<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class ProxyClosure implements DiAttributeInterface
{
    /**
     * @param class-string|non-empty-string $id class name or container identifier
     */
    public function __construct(private string $id)
    {
        if ('' === \trim($id)) {
            throw new AutowireAttributeException('The attribute #['.self::class.'] must have an $id parameter that is a non-empty string.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }
}
