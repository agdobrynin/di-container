<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(tags: new Tag('tags.one'))]
#[Autowire(tags: new Tag('tags.two'))]
final class FooConfigureAutowireAttr {}
