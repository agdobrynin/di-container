<?php

declare(strict_types=1);

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Tests\ContainerBuilder\Fixtures\Foo;
use Tests\ContainerBuilder\Fixtures2\Bat;
use Tests\ContainerBuilder\Fixtures2\Baz;

// configure definitions via `DefinitionsConfiguratorInterface`
return static function (DefinitionsConfiguratorInterface $configurator) {
    // get definition from `import`
    if (($foo = $configurator->getDefinition(Foo::class))
        && $foo instanceof DiDefinitionAutowire) {
        $foo->bindTag('tags.qux');
        $foo->bindArguments('secure');
    }

    // get definition from `addDefinitions`
    if (($baz = $configurator->getDefinition(Baz::class))
        && $baz instanceof DiDefinitionAutowire) {
        $baz->bindTag('tags.qux');
    }

    // get definition from `load`
    if (($bat = $configurator->getDefinition(Bat::class))
        && $bat instanceof DiDefinitionAutowire) {
        $bat->bindTag('tags.qux');
    }
};
