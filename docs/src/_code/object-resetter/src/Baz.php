<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Interfaces\ResetInterface;

final class Baz implements ResetInterface
{
    // The PHP-class has a large state.

    public function reset(): void
    {
        // reset class state.
    }
}