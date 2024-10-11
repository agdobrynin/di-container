<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\ClassWithInjectByAttributeTowServicesOneType;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class ContainerInjectTwoServicesOneTypeTest extends TestCase
{
    public function testInjectTwoServicesOneType(): void
    {
        $container = (new DiContainerFactory())->make();

        $class = $container->get(ClassWithInjectByAttributeTowServicesOneType::class);

        $this->assertEquals(['one', 'two'], $class->iterator1->getArrayCopy());
        $this->assertEquals(['three', 'four'], $class->iterator2->getArrayCopy());
    }
}
