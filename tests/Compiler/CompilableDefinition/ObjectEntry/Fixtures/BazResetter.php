<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\Fixtures;

final class BazResetter
{
    public static function doReset(Baz $baz): void {}
}
