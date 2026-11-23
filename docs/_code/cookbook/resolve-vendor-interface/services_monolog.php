<?php
declare(strict_types=1);

// file: /app/config/services_monolog.php [!code highlight]

use Monolog\Handler\SocketHandler;
use Monolog\Level;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield 'baz_logger.handler' => diAutowire(SocketHandler::class)
        // аргументы в конструктор PHP-класса SocketHandler[!code warning]
        ->bindArguments(
            'udp://remote-server:1234',
            Level::Debug,
        );
};
