<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments\Fixtures;

final class Quux implements QuuxInterface
{
    public function __construct(public readonly string $secure) {}
}
