<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Service;

final class Foo
{
    public function __construct(public readonly iterable $services) {}
}
