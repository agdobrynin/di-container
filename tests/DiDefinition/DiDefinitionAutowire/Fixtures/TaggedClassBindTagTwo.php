<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.handlers.one', ['priority' => 100, 'validated' => true])]
#[Tag('tags.validator.two', ['login' => 'required|min:5'])]
class TaggedClassBindTagTwo {}
