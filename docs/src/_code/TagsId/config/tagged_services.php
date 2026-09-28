<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php  // [!code highlight]

use App\Services\{Bar, Baz};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.internal');//[!code ++]

    yield diAutowire(Baz::class)
        ->bindTag('tags.internal');//[!code ++]
};
