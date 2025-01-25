<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class TaggedAs implements DiAttributeInterface
{
    /**
     * @param non-empty-string $name tag name
     */
    public function __construct(private string $name, private bool $isLazy = true)
    {
        if ('' === \trim($name)) {
            throw new AutowireAttributeException('The attribute #['.self::class.'] must have an $name parameter that is a non-empty string.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }
}
