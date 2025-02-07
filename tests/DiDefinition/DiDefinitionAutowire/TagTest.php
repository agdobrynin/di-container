<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagOne;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagTwo;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagTwoDefault;

use function Kaspi\DiContainer\tagOptions;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\tagOptions
 *
 * @internal
 */
class TagTest extends TestCase
{
    public function testTagsByBindTag(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one', tagOptions(priorityMethod: 'getTaggedPriority'))
            ->bindTag('tags.handler-two', ['exclude.compile' => true], priority: 1000)
            ->bindTag('tags.handler-three')
            ->setContainer($mockContainer)
        ;

        $this->assertEquals(
            [
                'tags.handler-one' => ['priorityMethod' => 'getTaggedPriority'],
                'tags.handler-two' => ['priority' => 1000, 'exclude.compile' => true],
                'tags.handler-three' => [],
            ],
            $def->getTags()
        );

        $this->assertTrue($def->hasTag('tags.handler-one'));
        $this->assertEquals(['priorityMethod' => 'getTaggedPriority'], $def->getTag('tags.handler-one'));
        $this->assertEquals(1000, $def->geTagPriority('tags.handler-one'));

        $this->assertTrue($def->hasTag('tags.handler-two'));
        $this->assertEquals(['priority' => 1000, 'exclude.compile' => true], $def->getTag('tags.handler-two'));
        $this->assertEquals(1000, $def->geTagPriority('tags.handler-two'));

        $this->assertEquals(1000, $def->geTagPriority('tags.handler-three', ['defaultPriorityMethod' => 'getTaggedPriority']));
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

    public static function dataProviderPriorityTagMethodWrongType(): \Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['   '];
    }

    /**
     * @dataProvider dataProviderPriorityTagMethodWrongType
     */
    public function testGetPriorityByPriorityTagMethodWrongType(mixed $priorityTagMethod): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one', tagOptions(priorityMethod: $priorityTagMethod))
            ->setContainer($mockContainer)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The value option must be non-empty string');

        $def->geTagPriority('tags.handler-one');
    }

    public static function dataProviderPriorityTagMethodByOptionsWrongType(): \Generator
    {
        yield 'empty string' => [tagOptions(priorityMethod: '')];

        yield 'string with sapces' => [tagOptions(priorityMethod: '   ')];

        yield 'array' => [['priorityMethod' => ['method']]];

        yield 'boolean' => [['priorityMethod' => true]];

        yield 'stdClass' => [['priorityMethod' => new \stdClass()]];
    }

    /**
     * @dataProvider dataProviderPriorityTagMethodByOptionsWrongType
     */
    public function testGetPriorityByPriorityTagMethodByOptionsWrongType(array $options): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one', options: $options)
            ->setContainer($mockContainer)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The value option must be non-empty string');

        $def->geTagPriority('tags.handler-one');
    }

    public function testGetPriorityByDefaultPriorityTagMethodSuccess(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one')
            ->setContainer($mockContainer)
        ;

        $this->assertEquals(1000, $def->geTagPriority('tags.handler-one', tagOptions(defaultPriorityMethod: 'getTaggedPriority')));
    }

    public function testGetPriorityByDefaultPriorityTagMethodFailIsRequiredFalse(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one')
            ->setContainer($mockContainer)
        ;

        $this->assertNull($def->geTagPriority('tags.handler-one', tagOptions(defaultPriorityMethod: 'getTaggedPriorityNonExist')));
    }

    public function testGetPriorityByDefaultPriorityTagMethodFailIsRequiredTrue(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one')
            ->setContainer($mockContainer)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('method must be declared with public and static modifiers. Return type must be int, string, null');

        $def->geTagPriority('tags.handler-one', tagOptions(defaultPriorityMethod: 'getTaggedPriorityNonExist', requireDefaultPriorityMethod: true));
    }

    public static function dataProviderWrongReturnType(): \Generator
    {
        yield 'none return' => [TaggedClassBindTagOne::class, 'getTaggedPriorityReturnEmpty'];

        yield 'array return' => [TaggedClassBindTagOne::class, 'getTaggedPriorityReturnArray'];

        yield 'wrong union type return' => [TaggedClassBindTagOne::class, 'getTaggedPriorityReturnUnionWrong'];
    }

    /**
     * @dataProvider dataProviderWrongReturnType
     */
    public function testWrongReturnType(string $class, string $method): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $def = (new DiDefinitionAutowire($class))
            ->bindTag('tags.handler-one', options: tagOptions(priorityMethod: $method))
            ->setContainer($mockContainer)
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('method must be declared with public and static modifiers. Return type must be int, string, null');

        $def->geTagPriority('tags.handler-one');
    }
}
