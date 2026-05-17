<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures2;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diRuntime;
use function Kaspi\DiContainer\diTaggedAs;

return static function () {
    yield diRuntime('service.foo', classDefinition: Foo::class)
        ->bindTag('foo')
    ;

    yield diAutowire(Bar::class)
        ->bindTag('foo')
    ;

    yield diAutowire(Baz::class)
        ->bindArguments(
            diTaggedAs('foo', priorityDefaultMethod: 'getPriority', keyDefaultMethod: 'getKey')
        )
    ;

    yield diAutowire(Qux::class)
        ->bindArguments(
            tagged: diTaggedAs(FooInterface::class)
        )
    ;
};
