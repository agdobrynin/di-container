<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

class LiteDependency
{
    public function __construct() {}

    public function doMake(): string
    {
        return 'doMake in LiteDependency';
    }
}
