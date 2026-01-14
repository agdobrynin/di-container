<?php

declare(strict_types=1);

namespace Tests\DiContainerFactory;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
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
        $definitions = static function (): Generator {
            yield 'a' => 'b';

            yield 'c' => static fn () => 'hello!';
        };

        $container = (new DiContainerFactory())->make($definitions());

        $this->assertInstanceOf(DiContainer::class, $container);
        $this->assertEquals('b', $container->get('a'));
        $this->assertEquals('hello!', $container->get('c'));
    }

    public function testMakeContainerByFactoryDefinitionInsertByArray(): void
    {
        $definitions = [
            'a' => 'b',
            'c' => static fn () => 'hello!',
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $this->assertInstanceOf(DiContainer::class, $container);
        $this->assertEquals('b', $container->get('a'));
        $this->assertEquals('hello!', $container->get('c'));
    }
}
