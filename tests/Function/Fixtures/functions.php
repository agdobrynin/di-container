<?php

declare(strict_types=1);

namespace Tests\Function\Fixtures;

function bar(string $param): string
{
    return 'foo + '.$param;
}
