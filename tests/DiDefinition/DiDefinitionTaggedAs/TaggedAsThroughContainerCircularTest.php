<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\One;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Service;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUse;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUseInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUseOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUseTwo;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Two;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\tagOptions
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 */
class TaggedAsThroughContainerCircularTest extends TestCase
{
    public function testCircularTaggedAsByPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(One::class)
                ->bindTag('tags.service-item'),
            diAutowire(Two::class)
                ->bindTag('tags.service-item'),
            diAutowire(Service::class)
                ->bindArguments(services: diTaggedAs('tags.service-item')),
        ]);

        $class = $container->get(Service::class);
        $this->assertInstanceOf(Service::class, $class);

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches('/Trying call cyclical dependency.+One.+Two.+One/');

        $class->services->valid();
    }

    public function testCircularTaggedAsByPhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Circular\Attributes\One::class),
            diAutowire(Fixtures\Circular\Attributes\Two::class),
        ]);

        $class = $container->get(Fixtures\Circular\Attributes\Service::class);

        $this->assertInstanceOf(Fixtures\Circular\Attributes\Service::class, $class);

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches('/Trying call cyclical dependency.+One.+Two.+One/');

        \iterator_to_array($class->services);
    }

    public function testCircularTaggedByInterfaceByPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ServiceUseOne::class),
            diAutowire(ServiceUseTwo::class),
            diAutowire(ServiceUse::class)
                ->bindArguments(services: diTaggedAs(ServiceUseInterface::class)),
        ]);

        $class = $container->get(ServiceUse::class);
        $this->assertInstanceOf(ServiceUse::class, $class);

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches('/Trying call cyclical dependency.+One.+Two.+One/');

        $class->services->valid();
    }

    public function testCircularTaggedByInterfaceByPhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Circular\Attributes\ServiceUseOne::class),
            diAutowire(Fixtures\Circular\Attributes\ServiceUseTwo::class),
        ]);

        $class = $container->get(Fixtures\Circular\Attributes\ServiceUse::class);

        $this->assertInstanceOf(Fixtures\Circular\Attributes\ServiceUse::class, $class);

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches('/Trying call cyclical dependency.+One.+Two.+One/');

        \iterator_to_array($class->services);
    }
}
