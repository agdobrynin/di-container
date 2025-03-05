<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\Import\SubDirectory;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isSingleton: true)]
#[Autowire(id: 'services.two', isSingleton: true)]
final class Two {}
