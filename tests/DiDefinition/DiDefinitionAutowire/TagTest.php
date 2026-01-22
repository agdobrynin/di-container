<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagOne;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagTwo;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagTwoDefault;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TagWrongPriorityMethod\Bar;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TagWrongPriorityMethod\Foo;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Tag::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
class TagTest extends TestCase
{
    public function testTagsByBindTag(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one', ['priority.method' => 'getTaggedPriority'])
            ->bindTag('tags.handler-two', ['exclude.compile' => true], priority: 1000)
            ->bindTag('tags.handler-three')
            ->setContainer($mockContainer)
        ;

        $this->assertEquals(
            [
                'tags.handler-one' => ['priority.method' => 'getTaggedPriority'],
                'tags.handler-two' => ['priority' => 1000, 'exclude.compile' => true],
                'tags.handler-three' => [],
            ],
            $def->getTags()
        );

        $this->assertTrue($def->hasTag('tags.handler-one'));
        $this->assertEquals(['priority.method' => 'getTaggedPriority'], $def->getTag('tags.handler-one'));
        $this->assertEquals(1000, $def->geTagPriority('tags.handler-one'));

        $this->assertTrue($def->hasTag('tags.handler-two'));
        $this->assertEquals(['priority' => 1000, 'exclude.compile' => true], $def->getTag('tags.handler-two'));
        $this->assertEquals(1000, $def->geTagPriority('tags.handler-two'));

        $this->assertEquals(1000, $def->geTagPriority('tags.handler-three', ['priority.default_method' => 'getTaggedPriority']));
    }

    public function testTagsByPhpAttribute(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')->willReturn(
            new DiContainerConfig()
        );

        $def = new DiDefinitionAutowire(TaggedClassBindTagTwoDefault::class);
        $def->setContainer($mockContainer);

        $this->assertTrue($def->hasTag('tags.handlers.magic'));
        $this->assertEquals([], $def->getTag('tags.handlers.magic'));
        $this->assertNull($def->geTagPriority('tags.handlers.magic'));
        $this->assertEquals(['tags.handlers.magic' => []], $def->getTags());
    }

    public function testTagsOverrideTagByPhpAttribute(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')->willReturn(
            new DiContainerConfig()
        );

        $def = (new DiDefinitionAutowire(TaggedClassBindTagTwoDefault::class))
            ->bindTag('tags.handlers.magic', ['exclude.compile' => true], priority: 100)
        ;
        $def->setContainer($mockContainer);

        $this->assertTrue($def->hasTag('tags.handlers.magic'));
        $this->assertEquals([], $def->getTag('tags.handlers.magic'));
        $this->assertEquals(0, $def->geTagPriority('tags.handlers.magic'));
        $this->assertEquals(['tags.handlers.magic' => []], $def->getTags());
    }

    public function testTagsByPhpAttributes(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')->willReturn(
            new DiContainerConfig()
        );

        $def = (new DiDefinitionAutowire(TaggedClassBindTagTwo::class))
            ->bindTag('tags.security')
        ;
        $def->setContainer($mockContainer);

        $this->assertTrue($def->hasTag('tags.security'));
        $this->assertEquals([], $def->getTag('tags.security'));
        $this->assertNull($def->geTagPriority('tags.security'));

        $this->assertTrue($def->hasTag('tags.handlers.one'));
        $this->assertEquals(['priority' => 100, 'validated' => true], $def->getTag('tags.handlers.one'));
        $this->assertEquals(100, $def->geTagPriority('tags.handlers.one'));

        $this->assertTrue($def->hasTag('tags.validator.two'));
        $this->assertEquals(['login' => 'required|min:5'], $def->getTag('tags.validator.two'));
        $this->assertEquals(0, $def->geTagPriority('tags.validator.two'));

        $this->assertEquals(
            [
                'tags.security' => [],
                'tags.handlers.one' => ['priority' => 100, 'validated' => true],
                'tags.validator.two' => ['login' => 'required|min:5'],
            ],
            $def->getTags()
        );
    }

    public function testTagsByPhpAttributesAndUnsetUseAttribute(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')->willReturn(
            new DiContainerConfig()
        );

        $def = (new DiDefinitionAutowire(TaggedClassBindTagTwo::class))
            ->bindTag('tags.security')
        ;
        $def->setContainer($mockContainer);

        $this->assertTrue($def->hasTag('tags.security'));
        $this->assertTrue($def->hasTag('tags.handlers.one'));
        $this->assertTrue($def->hasTag('tags.validator.two'));
    }

    #[DataProvider('dataProviderPriorityTagMethodWrongType')]
    public function testGetPriorityByPriorityTagMethodWrongType(mixed $priorityTagMethod): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one', ['priority.method' => $priorityTagMethod])
            ->setContainer($mockContainer)
        ;

        $def->geTagPriority('tags.handler-one');
    }

    public static function dataProviderPriorityTagMethodWrongType(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['   '];
    }

    #[DataProvider('dataProviderPriorityMethodByPhpAttributeWithWrongType')]
    public function testGetPriorityMethodByPhpAttributeWithWrongType(string $class, string $tagName): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('in the php attribute #[Tag]');

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('getConfig')->willReturn(
            new DiContainerConfig(
                useAttribute: true,
            )
        );

        $def = (new DiDefinitionAutowire($class))
            ->setContainer($container)
        ;

        $def->geTagPriority($tagName);
    }

    public static function dataProviderPriorityMethodByPhpAttributeWithWrongType(): Generator
    {
        yield 'provide by parameter $priorityMethod' => [Bar::class, 'tags.baz'];

        yield 'provide bt parameter $options with key "priority.method"' => [Foo::class, 'tags.baz'];
    }

    #[DataProvider('dataProviderPriorityTagMethodByOptionsWrongType')]
    public function testGetPriorityByPriorityTagMethodByOptionsWrongType(array $options): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Priority method must be present none-empty string.');

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one', options: $options)
            ->setContainer($mockContainer)
        ;

        $def->geTagPriority('tags.handler-one');
    }

    public static function dataProviderPriorityTagMethodByOptionsWrongType(): Generator
    {
        yield 'empty string' => [['priority.method' => '']];

        yield 'string with spaces' => [['priority.method' => '   ']];

        yield 'array' => [['priority.method' => ['method']]];

        yield 'boolean' => [['priority.method' => true]];

        yield 'stdClass' => [['priority.method' => new stdClass()]];
    }

    public function testGetPriorityByDefaultPriorityTagMethodSuccess(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one')
            ->setContainer($mockContainer)
        ;

        $this->assertEquals(1000, $def->geTagPriority('tags.handler-one', ['priority.default_method' => 'getTaggedPriority']));
    }

    public function testGetPriorityByDefaultPriorityTagMethodFailIsRequiredFalse(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one')
            ->setContainer($mockContainer)
        ;

        $this->assertNull($def->geTagPriority('tags.handler-one', ['priority.default_method' => 'getTaggedPriorityNonExist']));
    }

    #[DataProvider('dataProviderWrongReturnType')]
    public function testWrongReturnType(string $class, string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire($class))
            ->bindTag('tags.handler-one', options: ['priority.method' => $method])
            ->setContainer($mockContainer)
        ;

        $def->geTagPriority('tags.handler-one');
    }

    public static function dataProviderWrongReturnType(): Generator
    {
        yield 'array return' => [TaggedClassBindTagOne::class, 'getTaggedPriorityReturnArray'];

        yield 'wrong union type return' => [TaggedClassBindTagOne::class, 'getTaggedPriorityReturnUnionWrong'];
    }

    public function testGetTagWithoutSetContainer(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot check exist tag "tags.handler-one" on class');

        (new DiDefinitionAutowire(new ReflectionClass(new class {})))
            ->bindTag('tags.handler-one')
            ->hasTag('tags.handler-one')
        ;
    }

    public function testGetTagOnWrongDefinition(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get tag "tags.handler-one" on class');

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('getConfig')
            ->willReturn(
                new DiContainerConfig(
                    useAttribute: true,
                )
            )
        ;

        (new DiDefinitionAutowire(NoneExist::class))
            ->setContainer($container)
            ->bindTag('tags.handler-one')
            ->getTag('tags.handler-one')
        ;
    }
}
