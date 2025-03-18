<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportCreating\SubOne;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isSingleton: true)]
final class Two {}
