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
        // аргумент в конструктор PHP-класса Logger [!code warning]
        ->bindArguments(
            diParameter('app_logger.name'),
        )
        // внедрить зависимость в PHP-класс Logger через сеттер-метод [!code warning]
        ->setup(
            'pushHandler',
            arguments: [
                // PHP-класс StreamHandler как аргумент в сеттер-метод [!code warning]
                diAutowire(StreamHandler::class)
                    // аргументы в конструктор PHP-класса StreamHandler [!code warning]
                    ->bindArguments(
                        diParameter('app_logger.file'),
                        diParameter('app_logger.level'),
                    )
            ],
        );
};
