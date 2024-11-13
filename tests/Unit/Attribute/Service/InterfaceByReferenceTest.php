<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentInterfaceByReference;
use Tests\Unit\Attribute\Service\Fixtures\ServiceOne;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class InterfaceByReferenceTest extends TestCase
{
    public function testResolveInterfaceByReference(): void
    {
        $container = (new DiContainerFactory())->make([
            'services.serviceOne' => [
                ServiceOne::class,
                DiContainerInterface::ARGUMENTS => ['name' => 'Vice City'],
                DiContainerInterface::SINGLETON => true,
            ],
        ]);

        $class = $container->get(ClassInjectArgumentInterfaceByReference::class);

        $this->assertInstanceOf(ServiceOne::class, $class->service);
        $this->assertEquals('Vice City', $class->service->getName());

        $this->assertSame($class->service, $container->get(ClassInjectArgumentInterfaceByReference::class)->service);
    }
}
