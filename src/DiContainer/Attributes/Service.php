<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;

use function trim;

#[Attribute(Attribute::TARGET_CLASS)]
final class Service
{
    /**
     * @param class-string|non-empty-string $id class name or container identifier
     */
    public function __construct(public readonly string $id, public readonly ?bool $isSingleton = null)
    {
        if ('' === trim($id)) {
            throw new AutowireAttributeException('The $id parameter must be a non-empty string.');
        }
    }
}
