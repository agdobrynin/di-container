<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportCreating;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(id: 'services.any')]
final class Bar implements Interfaces\OtherInterface {}
