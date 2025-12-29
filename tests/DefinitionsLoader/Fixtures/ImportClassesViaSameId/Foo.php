<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportClassesViaSameId;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(id: 'services.one')]
final class Foo {}
