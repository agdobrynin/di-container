<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class InjectByCallable implements DiAttributeInterface
{
    /**
     * @param non-empty-string $callable
     */
    public function __construct(private string $callable, private bool $isSingleton = false)
    {
        if ('' === $callable || \str_contains($callable, ' ')) { // @phpstan-ignore identical.alwaysFalse
            throw new AutowireAttributeException(
                \sprintf('The $callable parameter must be a non-empty string and must not contain spaces. Got: "%s"', $callable)
            );
        }
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->callable;
    }
}
