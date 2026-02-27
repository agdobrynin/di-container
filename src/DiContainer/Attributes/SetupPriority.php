<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class SetupPriority
{
    public function __construct(public readonly int $priority = 0) {}
}
