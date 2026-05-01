<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures;

final class Foo
{
    public function __construct(public readonly Bar $bar, public readonly object $service) {}
}
