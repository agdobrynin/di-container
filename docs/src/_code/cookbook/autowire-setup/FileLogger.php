<?php
declare(strict_types=1);

// file: /app/src/Services/FileLogger.php [!code highlight]

namespace App\Services;

use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    public function __construct(public readonly string $fileName) {}

    // implement methods from \Psr\Log\LoggerInterface
}
