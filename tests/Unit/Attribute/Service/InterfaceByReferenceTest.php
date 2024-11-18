<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReference;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReferenceSpaceInIdentifier;
use Tests\Unit\Attribute\Service\Fixtures\ServiceOne;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\Attributes\ServiceByReference
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class InterfaceByReferenceTest extends TestCase
{
    public function testResolveInterfaceByReference(): void
    {
        $container = (new DiContainerFactory())->make([
            'services.serviceOne' => diAutowire(ServiceOne::class, ['name' => 'Vice City'], true),
        ]);

        $class = $container->get(ClassInjectArgumentInterfaceByReference::class);

        $this->assertInstanceOf(ServiceOne::class, $class->service);
        $this->assertEquals('Vice City', $class->service->getName());

        $this->assertSame($class->service, $container->get(ClassInjectArgumentInterfaceByReference::class)->service);
    }

    public function testResolveInterfaceByReferenceWithIdentifier(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(' must be a non-empty string');

        $container->get(ClassInjectArgumentInterfaceByReferenceSpaceInIdentifier::class);
    }
}
