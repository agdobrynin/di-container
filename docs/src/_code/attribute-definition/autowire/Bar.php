<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(arguments: [10])]//[!code ++]
#[Autowire(id: 'services.bar_20', arguments: [20])]//[!code ++]
final class Bar
{
    public function __construct(public readonly int $value) {}
}
