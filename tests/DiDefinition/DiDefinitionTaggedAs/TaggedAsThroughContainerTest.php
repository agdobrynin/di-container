<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDepByAttributeAsArray;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\One;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\TaggedAsCollection;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\Three;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\Two;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeaveDepWithDependency;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;
use function Kaspi\DiContainer\diValue;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 */
class TaggedAsThroughContainerTest extends TestCase
{
    public function testTaggedAsServicesWithoutPriorityAndLazy(): void
    {
        $container = (new DiContainerFactory())->make([
            'one' => diValue('services.one')->bindTag('tags.system.voters'),
            'two' => diValue('services.two'),
            'three' => diValue('services.three')->bindTag('tags.system.voters'),
        ]);

        $taggedServices = (new DiDefinitionTaggedAs('tags.system.voters', true))
            ->resolve($container)
        ;

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

        $taggedServices = (new DiDefinitionTaggedAs('tags.system.voters', true))
            ->resolve($container)
        ;

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

        $taggedServices = (new DiDefinitionTaggedAs('tags.system.voters', false)) // 🚩 lazy FALSE
            ->resolve($container)
        ;

        $this->assertCount(2, $taggedServices);

        // access by key as container identifier
        $this->assertEquals('services.three', $taggedServices['three']);
        $this->assertEquals('services.one', $taggedServices['one']);
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

    public function testTaggedAsServicesByAttributeFromContainerNotLazy(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(AnyClass::class),
            diAutowire(HeavyDepOne::class)
                ->bindTag('tags.heavy-dep'),
            diAutowire(HeaveDepWithDependency::class)
                ->bindArguments(someDep: [1, 2, 3])
                ->bindTag('tags.any-serv'),
            diAutowire(HeavyDepTwo::class)
                ->bindTag('tags.heavy-dep', ['priority' => 100]),
        ]);

        $res = $container->get(ClassWithHeavyDepByAttributeAsArray::class)->getDep();

        $this->assertCount(2, $res);
        // access by key as container identifier
        $this->assertInstanceOf(HeavyDepTwo::class, $res[HeavyDepTwo::class]);
        $this->assertInstanceOf(HeavyDepOne::class, $res[HeavyDepOne::class]);
    }

    public function testOverrideDiTaggedAsPhpAttributeTaggedAs(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(AnyClass::class)
                // override by php-attribute #[TaggedAs('tags.handler-attribute')]
                ->bindArguments(tagged: diTaggedAs('tags.heavy-dep')),
            diAutowire(HeavyDepOne::class)
                ->bindTag('tags.heavy-dep'),
            diAutowire(HeaveDepWithDependency::class)
                ->bindArguments(someDep: [1, 2, 3])
                ->bindTag('tags.handler-attribute'),
            diAutowire(HeavyDepTwo::class)
                ->bindTag('tags.heavy-dep', ['priority' => 100]),
        ]);
        $res = $container->get(AnyClass::class);

        $this->assertTrue($res->tagged->valid());
        $this->assertInstanceOf(HeaveDepWithDependency::class, $res->tagged->current());
        $this->assertEquals(HeaveDepWithDependency::class, $res->tagged->key()); // check key of tagged service
        $res->tagged->next();
        $this->assertFalse($res->tagged->valid());
    }

    public function testExcludePhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(One::class)
                ->bindTag('tags.aaa'),
            diAutowire(Two::class)
                ->bindTag('tags.aaa'),
            diAutowire(Three::class)
                ->bindTag('tags.aaa'),
            diAutowire(TaggedAsCollection::class)
                ->bindArguments(items: diTaggedAs('tags.aaa', containerIdExclude: [Two::class]))
                ->bindTag('tags.aaa'),
        ]);

        $items = $container->get(TaggedAsCollection::class)->items;

        $this->assertCount(2, $items);
        $this->assertInstanceOf(One::class, $items[One::class]);
        $this->assertInstanceOf(Three::class, $items[Three::class]);
    }

    public function testExcludePhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Exclude\Attribute\One::class),
            diAutowire(Fixtures\Exclude\Attribute\Two::class),
            diAutowire(Fixtures\Exclude\Attribute\Three::class),
            diAutowire(Fixtures\Exclude\Attribute\TaggedAsCollection::class),
        ]);

        $items = $container->get(Fixtures\Exclude\Attribute\TaggedAsCollection::class)->items;

        $this->assertCount(2, $items);
        $this->assertTrue($items->has(Fixtures\Exclude\Attribute\One::class));
        $this->assertTrue($items->has(Fixtures\Exclude\Attribute\Two::class));
    }
}
