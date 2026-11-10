<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php [!code highlight]

namespace App\Services;

final class FooService
{
    public function __construct(public readonly HeavyDependency $heavyDependency) {}
}
