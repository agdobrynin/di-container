<?php
// file: /app/src/Services/FileLogger.php
namespace App\Services;

use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    public function __construct(public readonly string $fileName) {}

    // implement methods from LoggerInterface
}
