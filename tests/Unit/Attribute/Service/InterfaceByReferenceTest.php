<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Unit\Attribute\Service\Fixtures\ByReferenceFirstInterface;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReference;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReferenceEmptyIdentifier;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReferenceNotFound;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReferenceSpaceInIdentifier;
use Tests\Unit\Attribute\Service\Fixtures\ServiceAttributeEmptyInterface;
use Tests\Unit\Attribute\Service\Fixtures\ServiceAttributeWithSpacesInIdInterface;
use Tests\Unit\Attribute\Service\Fixtures\ServiceOne;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
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

    public function testResolveInterfaceByReferenceSpaceIdentifier(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(' must be a non-empty string');

        $container->get(ClassInjectArgumentInterfaceByReferenceSpaceInIdentifier::class);
    }

    public function testResolveInterfaceByReferenceEmptyIdentifier(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(' must be a non-empty string');

        $container->get(ClassInjectArgumentInterfaceByReferenceEmptyIdentifier::class);
    }

    public function testResolveInterfaceByReferenceNotFoundIdentifier(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('services.serviceOne');

        $container->get(ClassInjectArgumentInterfaceByReferenceNotFound::class);
    }

    public function testResolveInterfaceByReferenceWithCircularCall(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches(
            '/ByReferenceFirstInterface.+ByReferenceSecondInterface.+ByReferenceFirstInterface/'
        );

        $container->get(ByReferenceFirstInterface::class);
    }

    public function testResolveByServiceAttributeWithEmptyId(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        $container->get(ServiceAttributeEmptyInterface::class);
    }

    public function testResolveByServiceAttributeWithSpacesInId(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        $container->get(ServiceAttributeWithSpacesInIdInterface::class);
    }
}
