<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class Inject
{
    public function __construct(public ?string $id = null, public array $arguments = []) {}
}
