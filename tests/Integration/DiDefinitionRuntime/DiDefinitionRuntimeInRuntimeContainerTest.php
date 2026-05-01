<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DiDefinitionRuntime\Fixtures\Foo;
use Tests\Integration\DiDefinitionRuntime\Fixtures\FooAttr;

/**
 * @internal
 */
#[CoversNothing]
class DiDefinitionRuntimeInRuntimeContainerTest extends TestCase
{
    public function testReplaceRuntimeDefinitionSuccess(): void
    {
        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                useAttribute: true,
            )
        ))
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->load(__DIR__.'/Fixtures/services.php')
            ->build()
        ;

        $instance = (object) ['foo' => 'Service bar'];

        $container->set('service.foo', $instance);

        // service configured via php attribute `Inject`
        self::assertSame($instance, $container->get(FooAttr::class)->service);
        // service configured via `bindArguments()`
        self::assertSame($instance, $container->get(Foo::class)->service);
    }

    public function testReplaceRuntimeDefinitionFail(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        // The definition at container identifier `'service.foo'` not replaced via `$container->set('service.foo', $instance)`
        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                useAttribute: true,
            )
        ))
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->load(__DIR__.'/Fixtures/services.php')
            ->build()
        ;

        $container->get(FooAttr::class);
    }
}
