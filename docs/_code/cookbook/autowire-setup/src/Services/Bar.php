<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    // …
}
