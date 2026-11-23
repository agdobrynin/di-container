<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield LoggerInterface::class => diAutowire(
        Logger::class,
        isSingleton: true
    )
        // The argument for the constructor of the PHP Logger class [!code warning]
        ->bindArguments(
            diParameter('app_logger.name'),
        )
        // Inject a dependency into the PHP Logger class via a setter method [!code warning]
        ->setup(
            'pushHandler',
            arguments: [
                // The StreamHandler PHP class as an argument for a setter method [!code warning]
                diAutowire(StreamHandler::class)
                    // arguments for the constructor of the PHP class StreamHandler [!code warning]
                    ->bindArguments(
                        diParameter('app_logger.file'),
                        diParameter('app_logger.level'),
                    )
            ],
        );
};
