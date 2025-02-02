<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular;

class Two
{
    public function __construct(public One $one) {}
}
