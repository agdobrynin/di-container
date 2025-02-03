<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDepByAttributeAsArray;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeaveDepWithDependency;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;
use function Kaspi\DiContainer\diValue;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\diValue
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
        $this->assertInstanceOf(HeavyDepTwo::class, $res[0]);
        $this->assertInstanceOf(HeavyDepOne::class, $res[1]);
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
        $res->tagged->next();
        $this->assertFalse($res->tagged->valid());
    }
}
