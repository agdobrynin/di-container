<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\Generated\Service0;
use Tests\Unit\Definition\Fixtures\Generated\Service6;
use Tests\Unit\Definition\Fixtures\Generated\ServiceImplementation;
use Tests\Unit\Definition\Fixtures\Generated\ServiceInterface;

/**
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class DefinitionAsDiDefinitionAutowireTest extends TestCase
{
    public function testBigDefinitions(): void
    {
        $definition = static function (): \Generator {
            for ($i = 0; $i <= 10; ++$i) {
                yield new DiDefinitionAutowire("Tests\\Unit\\Definition\\Fixtures\\Generated\\Service{$i}", true);
            }

            yield 'some_alias' => new DiDefinitionAutowire(Service6::class, true);

            yield new DiDefinitionAutowire(ServiceImplementation::class, true);

            yield ServiceInterface::class => ServiceImplementation::class;
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