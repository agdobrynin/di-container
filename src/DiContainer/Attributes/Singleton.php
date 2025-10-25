<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Singleton
{
    public function __construct(private bool $isSingleton) {}

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }
}
