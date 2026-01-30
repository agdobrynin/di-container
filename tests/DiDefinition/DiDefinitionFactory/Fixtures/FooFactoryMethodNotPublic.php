<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

final class FooFactoryMethodNotPublic
{
    private function make(): mixed {}
}
