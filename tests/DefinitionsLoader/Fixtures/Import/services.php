<?php

declare(strict_types=1);
use Tests\DefinitionsLoader\Fixtures\Import\One;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(One::class)
        ->bindArguments(token: 'foo-bar-baz')
    ;

    yield diAutowire(Tests\DefinitionsLoader\Fixtures\Import\SubDirectory\One::class)
        ->bindArguments(token: 'baz-bar-foo')
    ;
};
