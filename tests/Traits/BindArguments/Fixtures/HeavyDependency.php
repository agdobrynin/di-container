<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments\Fixtures;

final class HeavyDependency
{
    public function doMake(QuuxInterface $quux): object {}
}
