<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(
    tags: new Tag('tags.two')
)]
final class Qux {}
