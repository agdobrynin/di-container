<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments\Fixtures;

final class HeavyDependency
{
    public function doMake(QuuxInterface $quux): object {}
}
