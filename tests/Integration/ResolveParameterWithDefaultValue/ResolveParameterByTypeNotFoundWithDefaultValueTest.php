<?php

declare(strict_types=1);

namespace Tests\Integration\ResolveParameterWithDefaultValue;

use Kaspi\DiContainer\DiContainer;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ResolveParameterWithDefaultValue\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;

/**
 * @coversNothing
 *
 * @internal
 */
class ResolveParameterByTypeNotFoundWithDefaultValueTest extends TestCase
{
    public function testDefault(): void
    {
        $container = new DiContainer([
            diAutowire(Foo::class),
        ]);

        self::assertNull($container->get(Foo::class)->bar);
    }
}
