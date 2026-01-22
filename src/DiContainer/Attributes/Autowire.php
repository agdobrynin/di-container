<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Interfaces\Attributes\DiAttributeServiceInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Autowire implements DiAttributeServiceInterface
{
    public function __construct(private readonly string $id = '', private readonly ?bool $isSingleton = null) {}

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }
}
