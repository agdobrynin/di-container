<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TagWrongPriorityMethod;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

#[Tag(name: 'tags.baz', options: ['priority.method' => new DiGet('some_service')])]
final class Foo {}
