<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\DefaultPriorityMethodWrong;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.quux')]
final class Foo {}
