<?php
declare(strict_types=1);

// file: /app/src/Services/HeavyDependency.php [!code highlight]

namespace App\Services;

final class HeavyDependency
{
    public function __construct(public readonly BazService $bazService) {}
}
