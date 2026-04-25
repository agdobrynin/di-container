<?php

declare(strict_types=1);

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Tests\Integration\DiDefinitionRuntime\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diRuntime;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    yield diRuntime('service.foo');

    yield diAutowire(Foo::class)
        ->bindArguments(service: diGet('service.foo'))
    ;
};
