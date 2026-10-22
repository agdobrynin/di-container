<?php
declare(strict_types=1);

// file: /app/config/services/service_logger.php
use App\Services\FileLogger;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function(): Generator {
    yield diAutowire(FileLogger::class)
        ->bindArguments(
            diParameter('files.logger_file')
        );
};
