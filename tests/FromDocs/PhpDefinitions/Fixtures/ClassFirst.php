<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class ClassFirst implements ClassInterface
{
    public function __construct(public string $file) {}
}
