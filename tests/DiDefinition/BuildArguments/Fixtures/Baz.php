<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments\Fixtures;

final class Baz implements Bar, Foo
{
    public static function doMake(?QuuxInterface $quux = null): object {}
}
