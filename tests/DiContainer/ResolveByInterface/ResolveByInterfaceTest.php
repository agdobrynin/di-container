<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByInterface;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiContainer\ResolveByInterface\Fixtures\FreeInterface;
use Tests\DiContainer\ResolveByInterface\Fixtures\ServiceViaAttributeWithClassA;
use Tests\DiContainer\ResolveByInterface\Fixtures\ServiceViaAttributeWithClassInterface;
use Tests\DiContainer\ResolveByInterface\Fixtures\ServiceViaAttributeWithReferenceInterface;
use Tests\DiContainer\ResolveByInterface\Fixtures\ServiceViaAttributeWithReferenceIsSingletonFalseInterface;
use Tests\DiContainer\ResolveByInterface\Fixtures\SuperClass;
use Tests\DiContainer\ResolveByInterface\Fixtures\SuperInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionInvokableWrapper
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\CallableParserTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ResolveByInterfaceTest extends TestCase
{
    public function testResolveByInterfaceViaAttributeWithZeroConfig(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true
        );

        // ServiceViaAttributeWithClassInterface via Service attribute as singleton.
        $res = ($container = new DiContainer(config: $config))->get(ServiceViaAttributeWithClassInterface::class);

        $this->assertInstanceOf(ServiceViaAttributeWithClassA::class, $res);
        $this->assertSame($res, $container->get(ServiceViaAttributeWithClassInterface::class));
    }

    public function testResolveByInterfaceViaAttributeWithZeroConfigUseReference(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true
        );

        $def = [
            diAutowire(ServiceViaAttributeWithClassA::class),
            // ...
            'services.class-a' => diGet(ServiceViaAttributeWithClassA::class),
        ];

        $container = new DiContainer($def, config: $config);

        // Via Service attribute as none-singleton.
        $res = $container->get(ServiceViaAttributeWithReferenceIsSingletonFalseInterface::class);

        $this->assertInstanceOf(ServiceViaAttributeWithClassA::class, $res);
        $this->assertNotSame($res, $container->get(ServiceViaAttributeWithReferenceIsSingletonFalseInterface::class));
    }

    public function testResolveByInterfaceViaAttributeWithZeroConfigUseReferenceAndResolveInterfaceByCallableDefinition(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true
        );

        $def = [
            'class-a' => diCallable(static function () {
                return new ServiceViaAttributeWithClassA();
            }, isSingleton: true),
            // ...
            'services.class-a' => diGet('class-a'),
        ];

        $container = new DiContainer($def, config: $config);

        // Via Service attribute as singleton.
        $res = $container->get(ServiceViaAttributeWithReferenceInterface::class);

        $this->assertInstanceOf(ServiceViaAttributeWithClassA::class, $res);

        $this->assertSame($res, $container->get(ServiceViaAttributeWithReferenceInterface::class));
    }

    public function testResolveByInterfaceViaAttributeWithZeroConfigUseReferenceCircularException(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true
        );

        $def = [
            'services.class-b' => diGet('services.class-a'),
            // ...
            'services.class-a' => diGet('services.class-b'),
        ];

        $container = new DiContainer($def, config: $config);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/cyclical dependency.+ServiceViaAttributeWithReferenceInterface.+services.class-a/');

        $container->get(ServiceViaAttributeWithReferenceInterface::class);
    }

    public function testResolveByInterfaceWithZeroConfigOffAttributeOff(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false,
            useAttribute: false
        );

        $def = [
            ServiceViaAttributeWithClassInterface::class => diAutowire(ServiceViaAttributeWithClassA::class),
        ];

        $res = (new DiContainer(definitions: $def, config: $config))->get(ServiceViaAttributeWithClassInterface::class);

        $this->assertInstanceOf(ServiceViaAttributeWithClassA::class, $res);
    }

    public function testResolveByInterfaceWithZeroConfigOffUseReferenceAttributeOff(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false,
            useAttribute: false
        );

        $def = [
            'services.one' => diAutowire(ServiceViaAttributeWithClassA::class, true),
            // ...
            'services.x' => diGet('services.one'),
            // ...
            ServiceViaAttributeWithClassInterface::class => diGet('services.x'),
        ];

        $container = new DiContainer($def, config: $config);
        $res = $container->get(ServiceViaAttributeWithClassInterface::class);

        $this->assertInstanceOf(ServiceViaAttributeWithClassA::class, $res);
        // test singleton by config
        $this->assertSame($res, $container->get(ServiceViaAttributeWithClassInterface::class));
    }

    public function testResolveByInterfaceWithZeroConfigWithoutAttribute(): void
    {
        $container = new DiContainer(config: new DiContainerConfig());

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Definition not found for');

        $container->get(FreeInterface::class);
    }

    public function testResolveByInterfaceByCallableIsSingleton(): void
    {
        $def = [
            SuperInterface::class => diCallable(
                definition: static fn () => new SuperClass('aaa'),
                isSingleton: true,
            ),
        ];

        $container = new DiContainer($def, config: new DiContainerConfig());
        $res = $container->get(SuperInterface::class);

        $this->assertEquals('aaa', $res->getDependency());
        $this->assertSame($res, $container->get(SuperInterface::class));
    }

    public function testResolveByInterfaceByCallbackFunction(): void
    {
        $def = [
            SuperInterface::class => static fn () => new SuperClass('bbb'),
        ];

        $container = new DiContainer($def, config: new DiContainerConfig());
        $res = $container->get(SuperInterface::class);

        $this->assertEquals('bbb', $res->getDependency());
        $this->assertNotSame($res, $container->get(SuperInterface::class));
    }
}
