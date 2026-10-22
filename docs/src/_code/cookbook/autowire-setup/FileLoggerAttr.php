<?php
declare(strict_types=1);

// file: /app/src/Services/FileLogger.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Parameter;// [!code ++]
use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    public function __construct(
        #[Parameter('files.logger_file')]// [!code ++]
        public readonly string $fileName
    ) {}

    // implement methods from LoggerInterface
}
