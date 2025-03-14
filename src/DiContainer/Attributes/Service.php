<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;

use function trim;

#[Attribute(Attribute::TARGET_CLASS)]
final class Service implements DiAttributeServiceInterface
{
    /**
     * @param class-string|non-empty-string $id class name or container identifier
     */
    public function __construct(private string $id, private ?bool $isSingleton = null)
    {
        if ('' === trim($id)) {
            throw new AutowireAttributeException('The $id parameter must be a non-empty string.');
        }
    }

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }
}
