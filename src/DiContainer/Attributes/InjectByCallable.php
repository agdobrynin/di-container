<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

use function sprintf;
use function str_contains;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class InjectByCallable implements DiAttributeInterface
{
    /**
     * @param non-empty-string $callable
     */
    public function __construct(private string $callable)
    {
        if ('' === $callable || str_contains($callable, ' ')) { // @phpstan-ignore identical.alwaysFalse
            throw new AutowireAttributeException(
                sprintf('The attribute #[%s] must have $callable parameter as non-empty string and must not contain spaces. Got: "%s".', self::class, $callable)
            );
        }
    }

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->callable;
    }
}
