<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Service;

#[Service(SimpleDb::class)]
interface SimpleDbInterface
{
    public function insert(string $name);

    public function select(string $name): array;
}
