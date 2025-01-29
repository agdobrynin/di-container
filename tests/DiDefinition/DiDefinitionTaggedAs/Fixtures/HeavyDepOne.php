<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

class HeavyDepOne implements HeavyDepInterface
{
    public function fakeOf(): string
    {
        return 'fake of '.static::class;
    }
}
