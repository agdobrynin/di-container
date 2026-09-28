<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use function rand;

final class Baz
{
    // …
    public function calculateSomething(): int
    {
        // here are some calculations

        return rand(-100, 100); // test data
    }
}
