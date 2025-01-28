<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.handlers.magic')]
class TaggedClassBindTagTwoDefault {}
