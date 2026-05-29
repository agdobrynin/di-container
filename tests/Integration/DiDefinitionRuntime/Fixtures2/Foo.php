<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures2;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime('service.foo')]
#[Tag('foo.attr', priorityMethod: 'getPriority')]
final class Foo implements FooInterface
{
    public static function getKey(): string
    {
        return 'tag_foo';
    }

    public static function getPriority(): int
    {
        return 200;
    }
}
