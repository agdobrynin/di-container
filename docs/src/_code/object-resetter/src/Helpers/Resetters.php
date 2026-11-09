<?php
declare(strict_types=1);

// file: /app/src/Helpers/Resetters.php [!code highlight]

namespace App\Helpers;

use App\Services\Baz;

final class Resetters
{
    public static function resetObject(object $object): void
    {
        if ($object instanceof Baz) {
            // reset the state of `App\Services\Baz` here

            return;
        }
    }
}