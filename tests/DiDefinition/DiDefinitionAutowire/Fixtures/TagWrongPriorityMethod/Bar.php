<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TagWrongPriorityMethod;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.baz', priorityMethod: '   ')]
final class Bar {}
