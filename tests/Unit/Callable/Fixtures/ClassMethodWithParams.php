<?php

declare(strict_types=1);

namespace Tests\Unit\Callable\Fixtures;

class ClassMethodWithParams
{
    public function doSomething(\ArrayIterator $arrayIterator): bool
    {
        return $arrayIterator->ksort();
    }
}
