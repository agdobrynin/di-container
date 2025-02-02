<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular;

class Service
{
    public function __construct(public iterable $services) {}
}
