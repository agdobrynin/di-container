<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\SetupAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Setup;

final class SetupOnConstructor
{
    #[Setup('x')]
    public function __construct(private string $value) {}
}
