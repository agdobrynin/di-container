<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameterRuntime\Fixtures;

class Foo
{
    public function __construct(public string $bar, public int $baz) {}
}
