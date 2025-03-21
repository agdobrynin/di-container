<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success {
    function cube(int $a): int
    {
        return $a ** 2;
    }
}

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success\Math {
    function cube(int $a): string
    {
        return \sprintf('Pow of %d is %d', $a, $a ** 2);
    }
}
