<?php
declare(strict_types=1);

// file: /app/Services/Bar.php [!code highlight]

namespace App\Services;

use Psr\Log\LoggerInterface;

final class Bar
{
    public function __construct(
        public readonly LoggerInterface $logger
    ) {}

    // …
}
