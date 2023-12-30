<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Service
{
    public function __construct(public string $id) {}
}
