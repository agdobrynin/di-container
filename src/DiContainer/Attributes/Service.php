<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;
use Kaspi\DiContainer\Traits\ArgumentsInAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Service implements DiAttributeServiceInterface
{
    use ArgumentsInAttribute;

    /**
     * @param class-string $id class name
     */
    public function __construct(private string $id, array $arguments = [], private bool $isSingleton = false)
    {
        if ('' === \trim($id)) {
            throw new AutowiredAttributeException('Attribute #['.__CLASS__.'] argument [id] must be a non-empty string.');
        }

        $this->arguments = $arguments;
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }
}
