<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diTaggedAs;
use function Kaspi\DiContainer\diValue;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\TagsTrait
 */
class TaggedAsTest extends TestCase
{
    public function testTaggedAsEmptyLazy(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.empty', true))
            ->setContainer(new DiContainer([
                's1' => diValue(['ok']),
                's2' => diValue(['ok-ko']),
            ]))
        ;

        $this->assertEquals('tags.empty', $taggedAs->getDefinition());
        // return generator because definition marked as lazy.
        $this->assertFalse($taggedAs->getServicesTaggedAs()->valid());
    }

    public function testTaggedAsEmptyNotLazy(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.empty', false))
            ->setContainer(new DiContainer([
                's1' => diValue(['ok']),
                's2' => diValue(['ok-ko']),
            ]))
        ;

        $this->assertIsArray($taggedAs->getServicesTaggedAs());
        $this->assertCount(0, $taggedAs->getServicesTaggedAs());
    }

    public function testTaggedAsWithTagsOptions(): void
    {
        /** @var DiDefinitionValue $taggedAs */
        $taggedAs = diValue('services.one')
            ->bindTag('tags.system.voters')
            ->bindTag('services.one', ['priority' => 100, 'meta-data' => ['1', '2']])
        ;

        $this->assertEquals(
            [
                'tags.system.voters' => ['priority' => 0],
                'services.one' => ['priority' => 100, 'meta-data' => ['1', '2']],
            ],
            $taggedAs->getTags()
        );

        $this->assertEquals(['priority' => 0], $taggedAs->getTag('tags.system.voters'));
        $this->assertEquals(0, $taggedAs->getOptionPriority('tags.system.voters'));

        $this->assertEquals(100, $taggedAs->getOptionPriority('services.one'));

        $this->assertNull($taggedAs->getTag('tags.non-existent-tag'));
        $this->assertEquals(0, $taggedAs->getOptionPriority('tags.non-existent-tag'));
    }

    public function testTaggedAsServicesWithoutPriorityAndLazy(): void
    {
        $container = (new DiContainerFactory())->make([
            'one' => diValue('services.one')->bindTag('tags.system.voters'),
            'two' => diValue('services.two'),
            'three' => diValue('services.three')->bindTag('tags.system.voters'),
        ]);

        $taggedAs = (new DiDefinitionTaggedAs('tags.system.voters', true))
            ->setContainer($container)
        ;

        $taggedServices = $taggedAs->getServicesTaggedAs();

        $this->assertTrue($taggedServices->valid());
        $this->assertEquals('services.one', $taggedServices->current());
        $taggedServices->next();
        $this->assertEquals('services.three', $taggedServices->current());
        // test options (meta-data) of tag
        $taggedServices->next();
        $this->assertFalse($taggedServices->valid());
    }

    public function testTaggedAsServicesWithPriorityAndLazy(): void
    {
        $container = (new DiContainerFactory())->make([
            'one' => diValue('services.one')->bindTag('tags.system.voters', ['priority' => 20]),
            'two' => diValue('services.two'),
            'three' => diValue('services.three')->bindTag('tags.system.voters', ['priority' => 100]),
        ]);

        $taggedAs = (new DiDefinitionTaggedAs('tags.system.voters', true))
            ->setContainer($container)
        ;

        $taggedServices = $taggedAs->getServicesTaggedAs();

        $this->assertTrue($taggedServices->valid());
        $this->assertEquals('services.three', $taggedServices->current());
        $taggedServices->next();
        $this->assertEquals('services.one', $taggedServices->current());
        $taggedServices->next();
        $this->assertFalse($taggedServices->valid());
    }

    public function testTaggedAsServicesWithPriorityNotLazy(): void
    {
        $container = (new DiContainerFactory())->make([
            'one' => diValue('services.one')->bindTag('tags.system.voters'),
            'two' => diValue('services.two'),
            'three' => diValue('services.three')->bindTag('tags.system.voters', ['priority' => 1000]),
        ]);

        $taggedAs = (new DiDefinitionTaggedAs('tags.system.voters', false)) // 🚩 lazy FALSE
            ->setContainer($container)
        ;

        $taggedServices = $taggedAs->getServicesTaggedAs();
        $this->assertCount(2, $taggedServices);

        $this->assertEquals('services.three', $taggedServices[0]);
        $this->assertEquals('services.one', $taggedServices[1]);
    }

    public function testTaggedAsServicesFromContainer(): void
    {
        $container = (new DiContainerFactory())->make([
            'one' => diValue('services.one')->bindTag('tags.system.voters', ['priority' => 99]),
            'two' => diValue('services.two'),
            'three' => diValue('services.three')->bindTag('tags.system.voters', ['priority' => 1000]),
            'voters' => diTaggedAs('tags.system.voters'),
        ]);

        $voters = $container->get('voters');
        $this->assertTrue($voters->valid());
        $this->assertEquals('services.three', $voters->current());
        $voters->next();
        $this->assertEquals('services.one', $voters->current());
        $voters->next();
        $this->assertFalse($voters->valid());
    }
}
