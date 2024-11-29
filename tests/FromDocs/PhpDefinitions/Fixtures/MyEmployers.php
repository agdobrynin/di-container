<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class MyEmployers
{
    public function __construct(public array $employers, public string $type) {}
}
