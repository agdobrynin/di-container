<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

class HeavyDependency
{
    public function __construct()
    {
        // Heavy dependency init.
    }

    public function doMake(): string
    {
        return 'doMake in HeavyDependency';
    }
}
