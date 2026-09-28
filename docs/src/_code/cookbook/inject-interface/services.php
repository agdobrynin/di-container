<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield QuxInterface::class => diAutowire(Bar::class);//[!code ++]
};
