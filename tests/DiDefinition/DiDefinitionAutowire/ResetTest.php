<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Tag::class)]
#[CoversClass(TagsTrait::class)]
class ResetTest extends TestCase
{
    public function testResetClassWithAttributes(): void
    {
        $config = $this->createMock(DiContainerConfigInterface::class);
        $config->method('isUseAttribute')
            ->willReturn(true)
        ;

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('getConfig')
            ->willReturn($config)
        ;

        $definition = new DiDefinitionAutowire(Foo::class);
        $definition->setContainer($container);

        self::assertEquals(Foo::class, $definition->getDefinition()->name);
        self::assertArrayHasKey('tags.srv', $definition->getTags());

        $definition->reset();

        self::assertEquals(Foo::class, $definition->getDefinition()->name);
        self::assertArrayHasKey('tags.srv', $definition->getTagsByAttribute());

        $this->expectException(DiDefinitionExceptionInterface::class);
        // `$definition->reset()` reset container too.
        $definition->getTags();
    }

    public function testResetClassWithDefinitionAsReflectionClass(): void
    {
        $container = $this->createMock(DiContainerInterface::class);

        $definition = new DiDefinitionAutowire(new ReflectionClass(Bar::class));
        $definition->setContainer($container);

        self::assertEquals(Bar::class, $definition->getDefinition()->name);
        self::assertIsArray($definition->getTags());

        $definition->reset();

        self::assertEquals(Bar::class, $definition->getDefinition()->name);

        $this->expectException(DiDefinitionExceptionInterface::class);
        // `$definition->reset()` reset container too.
        $definition->getTags();
    }
}

#[Tag('tags.srv')]
final class Foo
{
    public function __construct(public readonly Bar $bar) {}
}

final class Bar {}
