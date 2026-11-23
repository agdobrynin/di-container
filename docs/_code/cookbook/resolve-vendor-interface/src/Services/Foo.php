<?php
declare(strict_types=1);

// file: /app/Services/Foo.php [!code highlight]

namespace App\Services;

use Psr\Log\LoggerInterface;

final class Foo
{
    public function __construct(
        public readonly LoggerInterface $logger
    ) {}

    // …
}
