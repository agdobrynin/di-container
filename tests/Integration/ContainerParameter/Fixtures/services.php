<?php

declare(strict_types=1);

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Tests\Integration\ContainerParameter\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diParameter;

return static function (DefinitionsConfiguratorInterface $configurator) {
    $configurator->setParameter('port', 8080);

    yield diAutowire(Foo::class)
        ->bindArguments(endpoint: diParameter())
    ;
};
