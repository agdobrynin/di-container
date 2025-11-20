<?php

declare(strict_types=1);

namespace Tests\Integration\ResolveParameterWithDefaultValue;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Integration\ResolveParameterWithDefaultValue\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @coversNothing
 *
 * @internal
 */
class ExceptionMessageWhenBindArgIsNamedArgumentTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$bar in .+Foo::__construct()/');

        $container = (new DiContainerFactory())
            ->make([
                diAutowire(Foo::class)
                    ->bindArguments(bar: diGet('services.one')),
            ])
        ;

        $container->get(Foo::class);
    }
}
