<?php

declare(strict_types=1);

namespace Tests\DiDefinitionCompile\Fixtures\DiValue;

enum FooEnum: string
{
    case Bar = 'value bar';
    case Baz = 'value baz';
}
