<?php
declare(strict_types=1);

// file: /app/config/parameters.php [!code highlight]

use Monolog\Level;

return [
    'app_logger.name' => 'my-app_logger',
    'app_logger.file' => '/var/logs/app.log',
    'app_logger.level' => Level::Warning,
];
