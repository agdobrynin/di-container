<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagOne;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagTwo;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\TaggedClassBindTagTwoDefault;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class TagTest extends TestCase
{
    public function testTagsByBindTag(): void
    {
        $def = (new DiDefinitionAutowire(TaggedClassBindTagOne::class))
            ->bindTag('tags.handler-one')
            ->bindTag('tags.handler-two', ['priority' => 1000, 'exclude.compile' => true])
        ;

        $this->assertEquals(
            [
                'tags.handler-one' => ['priority' => 0],
                'tags.handler-two' => ['priority' => 1000, 'exclude.compile' => true],
            ],
            $def->getTags()
        );

        $this->assertTrue($def->hasTag('tags.handler-one'));
        $this->assertEquals(['priority' => 0], $def->getTag('tags.handler-one'));
        $this->assertTrue($def->hasTag('tags.handler-two'));
        $this->assertEquals(['priority' => 1000, 'exclude.compile' => true], $def->getTag('tags.handler-two'));
        $this->assertEquals(1000, $def->getOptionPriority('tags.handler-two'));
    }

    public function testTagsByPhpAttribute(): void
    {
        $def = new DiDefinitionAutowire(TaggedClassBindTagTwoDefault::class);
        $def->setUseAttribute(true);

        $this->assertTrue($def->hasTag('tags.handlers.magic'));
        $this->assertEquals(['priority' => 0], $def->getTag('tags.handlers.magic'));
        $this->assertEquals(0, $def->getOptionPriority('tags.handlers.magic'));
        $this->assertEquals(['tags.handlers.magic' => ['priority' => 0]], $def->getTags());
    }

    public function testTagsOverrideTagByPhpAttribute(): void
    {
        $def = (new DiDefinitionAutowire(TaggedClassBindTagTwoDefault::class))
            ->bindTag('tags.handlers.magic', ['priority' => 100, 'exclude.compile' => true])
        ;
        $def->setUseAttribute(true);

        $this->assertTrue($def->hasTag('tags.handlers.magic'));
        $this->assertEquals(['priority' => 0], $def->getTag('tags.handlers.magic'));
        $this->assertEquals(0, $def->getOptionPriority('tags.handlers.magic'));
        $this->assertEquals(['tags.handlers.magic' => ['priority' => 0]], $def->getTags());
    }

    public function testTagsByPhpAttributes(): void
    {
        $def = (new DiDefinitionAutowire(TaggedClassBindTagTwo::class))
            ->bindTag('tags.security')
        ;
        $def->setUseAttribute(true);

        $this->assertTrue($def->hasTag('tags.security'));
        $this->assertEquals(['priority' => 0], $def->getTag('tags.security'));
        $this->assertEquals(0, $def->getOptionPriority('tags.security'));

        $this->assertTrue($def->hasTag('tags.handlers.one'));
        $this->assertEquals(['priority' => 100, 'validated' => true], $def->getTag('tags.handlers.one'));
        $this->assertEquals(100, $def->getOptionPriority('tags.handlers.one'));

        $this->assertTrue($def->hasTag('tags.validator.two'));
        $this->assertEquals(['login' => 'required|min:5'], $def->getTag('tags.validator.two'));
        $this->assertEquals(0, $def->getOptionPriority('tags.validator.two'));

        $this->assertEquals(
            [
                'tags.security' => ['priority' => 0],
                'tags.handlers.one' => ['priority' => 100, 'validated' => true],
                'tags.validator.two' => ['login' => 'required|min:5'],
            ],
            $def->getTags()
        );
    }

    public function testTagsByPhpAttributesAndUnsetUseAttribute(): void
    {
        $def = (new DiDefinitionAutowire(TaggedClassBindTagTwo::class))
            ->bindTag('tags.security')
        ;
        $def->setUseAttribute(true);

        $this->assertTrue($def->hasTag('tags.security'));
        $this->assertTrue($def->hasTag('tags.handlers.one'));
        $this->assertTrue($def->hasTag('tags.validator.two'));

        $def->setUseAttribute(false);

        $this->assertTrue($def->hasTag('tags.security'));
        $this->assertFalse($def->hasTag('tags.handlers.one'));
        $this->assertFalse($def->hasTag('tags.validator.two'));
    }
}
