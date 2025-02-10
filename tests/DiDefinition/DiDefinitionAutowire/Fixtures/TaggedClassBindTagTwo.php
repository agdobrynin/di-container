<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.handlers.one', ['validated' => true], priority: 100)]
#[Tag('tags.validator.two', ['login' => 'required|min:5'])]
class TaggedClassBindTagTwo {}
