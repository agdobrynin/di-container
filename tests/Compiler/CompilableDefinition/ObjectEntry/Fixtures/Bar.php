<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures;

use Kaspi\DiContainer\Interfaces\ResetInterface;

final class Bar implements ResetInterface
{
    public function reset(): void
    {
        // TODO: Implement reset() method.
    }
}
