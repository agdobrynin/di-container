<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
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
        $definition = new DiDefinitionAutowire(Foo::class);
        self::assertEquals(Foo::class, $definition->getDefinition()->name);
        self::assertArrayHasKey('tags.srv', $definition->getTagsByAttribute());

        $definition->reset();

        self::assertEquals(Foo::class, $definition->getDefinition()->name);
        self::assertArrayHasKey('tags.srv', $definition->getTagsByAttribute());
    }

    public function testResetClassWithDefinitionAsReflectionClass(): void
    {
        $definition = new DiDefinitionAutowire(new ReflectionClass(Bar::class));
        self::assertEquals(Bar::class, $definition->getDefinition()->name);

        $definition->reset();

        self::assertEquals(Bar::class, $definition->getDefinition()->name);
    }
}

#[Tag('tags.srv')]
final class Foo
{
    public function __construct(public readonly Bar $bar) {}
}

final class Bar {}
