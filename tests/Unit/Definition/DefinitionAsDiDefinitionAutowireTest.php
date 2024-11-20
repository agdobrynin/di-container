<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\Generated\Service0;
use Tests\Unit\Definition\Fixtures\Generated\Service6;
use Tests\Unit\Definition\Fixtures\Generated\ServiceImplementation;
use Tests\Unit\Definition\Fixtures\Generated\ServiceInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class DefinitionAsDiDefinitionAutowireTest extends TestCase
{
    public function testBigDefinitions(): void
    {
        $definition = static function (): \Generator {
            for ($i = 0; $i <= 10; ++$i) {
                yield diAutowire("Tests\\Unit\\Definition\\Fixtures\\Generated\\Service{$i}");
            }

            yield 'some_alias' => diAutowire(Service6::class);

            yield diAutowire(ServiceImplementation::class);

            yield ServiceInterface::class => diReference(ServiceImplementation::class);
        };

        $container = (new DiContainerFactory())->make($definition());

        for ($i = 1; $i <= 10; ++$i) {
            $class = $container->get("Tests\\Unit\\Definition\\Fixtures\\Generated\\Service{$i}");

            $this->assertInstanceOf("Tests\\Unit\\Definition\\Fixtures\\Generated\\Service{$i}", $class);
            $this->assertInstanceOf(Service0::class, $class->service);
        }

        $this->assertInstanceOf(Service0::class, $container->get(Service0::class));

        $this->assertInstanceOf(Service6::class, $container->get('some_alias'));
        $this->assertInstanceOf(Service0::class, $container->get('some_alias')->service);

        $this->assertInstanceOf(ServiceImplementation::class, $container->get(ServiceInterface::class));
        $this->assertInstanceOf(Service0::class, $container->get(ServiceInterface::class)->service);
    }
}
