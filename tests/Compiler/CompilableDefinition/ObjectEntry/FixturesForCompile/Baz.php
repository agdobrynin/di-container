<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile;

use Kaspi\DiContainer\Interfaces\ResetInterface;

final class Baz implements ResetInterface
{
    private string $baz = 'Lorem ipsum baz';

    public function getBaz(): string
    {
        return $this->baz;
    }

    public function reset(): void
    {
        $this->baz = 'reset baz';
    }

    public function doResetManually(): void
    {
        $this->baz = 'manually baz';
    }
}
