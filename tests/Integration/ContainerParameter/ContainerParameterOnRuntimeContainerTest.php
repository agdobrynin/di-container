<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameter;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ContainerParameter\Fixtures\Foo;
use Tests\Integration\ContainerParameter\Fixtures\FooAttr;

/**
 * @internal
 */
#[CoversNothing]
class ContainerParameterOnRuntimeContainerTest extends TestCase
{
    public function testResolveParameterInConstructor(): void
    {
        $container = (new DiContainerBuilder())
            ->load(__DIR__.'/Fixtures/services.php')
            ->loadParameters(__DIR__.'/Fixtures/parameters.php')
            ->build()
        ;

        self::assertEquals(
            'example.com:8080',
            $container->get(Foo::class)->endpoint
        );

        self::assertEquals(
            'example.com:8080',
            $container->get(FooAttr::class)->endpoint
        );
    }

    #[TestWith([Foo::class])]
    #[TestWith([FooAttr::class])]
    public function testParameterNotRegistered(string $resolveClass): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $container = (new DiContainerBuilder())
            ->load(__DIR__.'/Fixtures/services_not_set_parameter_port.php')
            ->loadParameters(__DIR__.'/Fixtures/parameters.php')
            ->build()
        ;

        $container->get($resolveClass);
    }
}
