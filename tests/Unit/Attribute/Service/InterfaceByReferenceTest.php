<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Attribute\Service\Fixtures\ClassInjectArgumentByInterface;
use Tests\Unit\Attribute\Service\Fixtures\ServiceOne;

/**
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

        $class = $container->get(ClassInjectArgumentByInterface::class);

        $this->assertInstanceOf(ServiceOne::class, $class->service);
        $this->assertEquals('Vice City', $class->service->name);

        $this->assertSame($class->service, $container->get(ClassInjectArgumentByInterface::class)->service);
    }
}
