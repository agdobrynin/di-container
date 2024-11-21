<?php

declare(strict_types=1);

namespace Tests\DiContainerFactory;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 *
 * @internal
 */
class MainTest extends TestCase
{
    public function testMakeContainerByFactory(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertInstanceOf(DiContainer::class, $container);
    }

    public function testMakeContainerByFactoryDefinitionInsertByGenerator(): void
    {
        $definitions = static function () {
            yield 'a' => 'b';

            yield 'c' => static fn () => 'hello!';
        };

        $container = (new DiContainerFactory())->make($definitions());

        $this->assertInstanceOf(DiContainer::class, $container);
        $this->assertEquals('b', $container->get('a'));
        $this->assertEquals('hello!', $container->get('c'));
    }
}
