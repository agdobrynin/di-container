<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Interfaces\ResetInterface;

final class Qux implements ResetInterface
{

    public function reset(): void
    {
        // reset class state here
    }
}