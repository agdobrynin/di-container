<?php
declare(strict_types=1);

// file: /app/src/Services/BarService.php [!code highlight]

namespace App\Services;

final class BarService
{
    public function __construct(
        public readonly HeavyDependency $heavyDependency
    ) {}
}
