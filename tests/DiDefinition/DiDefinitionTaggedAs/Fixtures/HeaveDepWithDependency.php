<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use function var_export;

class HeaveDepWithDependency implements HeavyDepInterface
{
    public function __construct(private $someDep) {}

    public function fakeOf(): string
    {
        return 'fake-of: '.static::class.' '.var_export($this->someDep, true);
    }
}
