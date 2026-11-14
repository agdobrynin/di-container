<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Qux
{
    public function __construct(
        public readonly QuxInterface $qux
    ) {}
}
