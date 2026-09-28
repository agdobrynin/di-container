<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(arguments: ['Lorem ipsum'])]//[!code ++]
final class Foo
{
    public function __construct(public readonly string $value) {}
}
