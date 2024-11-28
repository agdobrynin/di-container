<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class ClassWithDependency
{
    public function __construct(public \SplFileInfo $splFileInfo) {}
}
