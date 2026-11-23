<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php // [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class FooService
{
    public function __construct(
        public readonly BatService $bat,
        public readonly QuxInterface $qux,
        public readonly string $adminEmail
    ) {}
}
