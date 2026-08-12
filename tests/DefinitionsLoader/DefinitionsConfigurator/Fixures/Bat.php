<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;
use Psr\Container\ContainerInterface;

#[Autowire(
    tags: [
        new Tag(ContainerInterface::class),
        new Tag('tags.one'),
    ],
)]
final class Bat {}
