<?php
declare(strict_types=1);

// file: /app/config/callable_entries.php// [!code highlight]

use App\Services\{Foo, Baz};

use function Kaspi\DiContainer\{diAutowire, diCallable};

return static function(): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            diCallable([Baz::class, 'factoryMethod'])//[!code ++]
                ->bindArguments(//[!code ++]
                    name: 'Lorem ipsum'//[!code ++]
                )//[!code ++]
        );
};
