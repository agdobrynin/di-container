<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Closure;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDep;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDepAsArray;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\ClassWithHeavyDepByAttribute;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\HeavyDepTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 *
 * @internal
 */
class TaggedAsProxyClosureTest extends TestCase
{
    public function testGetTaggedByArgumentsLazy(): void
    {
        $container = $this->makeContainer(ClassWithHeavyDep::class, true);

        $service = $container->get(ClassWithHeavyDep::class)->getDep();

        $this->assertIsIterable($service);

        $this->assertInstanceOf(Closure::class, $service->current());
        $this->assertInstanceOf(HeavyDepTwo::class, ($service->current())());

        $service->next();

        $this->assertInstanceOf(Closure::class, $service->current());
        $this->assertInstanceOf(HeavyDepOne::class, ($service->current())());

        $service->next();

        $this->assertFalse($service->valid());
    }

    public function testGetTaggedByAttributesLazy(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ClassWithHeavyDepByAttribute::class),
            'heavy.one' => diProxyClosure(HeavyDepOne::class)
                ->bindTag('tags.heavy.dep'),
            'heavy.two' => diProxyClosure(HeavyDepTwo::class)
                ->bindTag('tags.heavy.dep', ['priority' => 100]),
            'heavy.three' => diProxyClosure(HeavyDepTwo::class)
                ->bindTag('tags.other', ['priority' => 0]),
        ]);

        $service = $container->get(ClassWithHeavyDepByAttribute::class)->getDep();

        $this->assertIsIterable($service);

        $this->assertInstanceOf(Closure::class, $service->current());
        $this->assertInstanceOf(HeavyDepTwo::class, ($service->current())());

        $service->next();
        $secondService = $service->current();
        $this->assertInstanceOf(Closure::class, $secondService);
        $this->assertInstanceOf(HeavyDepOne::class, ($secondService)());

        $service->next();
        $this->assertFalse($service->valid());
    }

    public function testGetTaggedByArgumentsNotLazy(): void
    {
        $container = $this->makeContainer(ClassWithHeavyDepAsArray::class, false);

        $services = $container->get(ClassWithHeavyDepAsArray::class)->getDep();

        $this->assertCount(2, $services);

        // access by key as container identifier
        $this->assertInstanceOf(Closure::class, $services['heavy.two']);
        $this->assertInstanceOf(HeavyDepTwo::class, ($services['heavy.two'])());

        // access by key as container identifier
        $this->assertInstanceOf(Closure::class, $services['heavy.one']);
        $this->assertInstanceOf(HeavyDepOne::class, ($services['heavy.one'])());
    }

    protected function makeContainer(string $classWithHeavyDep, bool $isLazy): DiContainer
    {
        return (new DiContainerFactory())->make([
            diAutowire($classWithHeavyDep)
                ->bindArguments(diTaggedAs('tags.heavy.dep', $isLazy))
                ->bindTag('tags.ok-ko'),
            'heavy.one' => diProxyClosure(HeavyDepOne::class)
                ->bindTag('tags.heavy.dep'),
            'heavy.heavy-dep-too-class' => diProxyClosure(HeavyDepTwo::class)
                ->bindTag('tags.single'), // no tag 'tags.heavy.dep'
            'heavy.two' => diProxyClosure(HeavyDepTwo::class)
                ->bindTag('tags.heavy.dep', ['priority' => 100]),
        ]);
    }
}
