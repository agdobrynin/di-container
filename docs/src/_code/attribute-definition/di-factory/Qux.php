<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php // [!code highlight]

namespace App\Services;

final class Qux
{
    public function __construct(public readonly string $value) {}
}
