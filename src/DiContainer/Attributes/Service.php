<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Service implements DiAttributeServiceInterface
{
    /**
     * @param class-string $id class name
     */
    public function __construct(private string $id, private array $arguments = [], private bool $isSingleton = false)
    {
        if ('' === $id) {
            throw new AutowiredAttributeException('Attribute #['.__CLASS__.'] argument [id] must be a non-empty string.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }
}
