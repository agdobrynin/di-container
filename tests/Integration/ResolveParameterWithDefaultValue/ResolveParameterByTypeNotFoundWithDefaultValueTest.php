<?php

declare(strict_types=1);

namespace Tests\Integration\ResolveParameterWithDefaultValue;

use Kaspi\DiContainer\DiContainer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ResolveParameterWithDefaultValue\Fixtures\Bar;
use Tests\Integration\ResolveParameterWithDefaultValue\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class ResolveParameterByTypeNotFoundWithDefaultValueTest extends TestCase
{
    public function testDefault(): void
    {
        $container = new DiContainer([
            diAutowire(Foo::class),
        ]);

        self::assertNull($container->get(Foo::class)->bar);
    }

    public function testDefaultValueAsObject(): void
    {
        $container = new DiContainer([
            diAutowire(Bar::class),
        ]);

        self::assertEquals(['foo', 'bar'], $container->get(Bar::class)->bar->getArrayCopy());
    }
}
