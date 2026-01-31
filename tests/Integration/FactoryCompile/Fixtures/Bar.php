<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile\Fixtures;

final class Bar
{
    public function __construct(public Baz $baz) {}
}
