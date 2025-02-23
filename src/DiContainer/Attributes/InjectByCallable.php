<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class InjectByCallable implements DiAttributeInterface
{
    /**
     * @param non-empty-string $callable
     */
    public function __construct(private string $callable) {}

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->callable;
    }
}
