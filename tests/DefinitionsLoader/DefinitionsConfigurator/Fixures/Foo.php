<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(
    tags: [
        new Tag(QuxInterface::class),
        new Tag('tags.one'),
    ]
)]
final class Foo {}
