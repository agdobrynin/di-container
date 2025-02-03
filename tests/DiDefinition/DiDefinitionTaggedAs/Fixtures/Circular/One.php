<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular;

class One
{
    public function __construct(public Two $two) {}
}
