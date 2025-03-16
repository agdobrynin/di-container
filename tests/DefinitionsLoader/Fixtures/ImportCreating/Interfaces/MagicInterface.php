<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportCreating\Interfaces;

use Kaspi\DiContainer\Attributes\Service;
use Tests\DefinitionsLoader\Fixtures\ImportCreating\SubTwo\Three;

#[Service(Three::class, isSingleton: true)]
interface MagicInterface {}
