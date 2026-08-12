<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\Classes;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;
use Psr\Container\ContainerInterface;

#[Autowire(
    tags: new Tag(ContainerInterface::class)
)]
final class Baz {}
