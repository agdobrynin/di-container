<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\One;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Service;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUse;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUseInterface;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUseOne;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\ServiceUseTwo;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Two;

use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 */
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversFunction('Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Tag::class)]
#[CoversClass(TaggedAs::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(Helper::class)]
#[CoversClass(LazyDefinitionIterator::class)]
class TaggedAsThroughContainerCircularTest extends TestCase
{
    public function testCircularTaggedAsByPhpDefinition(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+One::__construct()/');

        $container = (new DiContainerFactory())->make([
            diAutowire(One::class)
                ->bindTag('tags.service-item'),
            diAutowire(Two::class)
                ->bindTag('tags.service-item'),
            diAutowire(Service::class)
                ->bindArguments(services: diTaggedAs('tags.service-item')),
        ]);

        $class = $container->get(Service::class);
        $this->assertInstanceOf(Service::class, $class);

        $class->services->current();
    }

    public function testCircularTaggedAsByPhpAttribute(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+One::__construct()/');

        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Circular\Attributes\One::class),
            diAutowire(Fixtures\Circular\Attributes\Two::class),
        ]);

        $class = $container->get(Fixtures\Circular\Attributes\Service::class);

        $this->assertInstanceOf(Fixtures\Circular\Attributes\Service::class, $class);

        iterator_to_array($class->services);
    }

    public function testCircularTaggedByInterfaceByPhpDefinition(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+ServiceUseOne::__construct()/');

        $container = (new DiContainerFactory())->make([
            diAutowire(ServiceUseOne::class),
            diAutowire(ServiceUseTwo::class),
            diAutowire(ServiceUse::class)
                ->bindArguments(services: diTaggedAs(ServiceUseInterface::class)),
        ]);

        $class = $container->get(ServiceUse::class);
        $this->assertInstanceOf(ServiceUse::class, $class);

        $class->services->current();
    }

    public function testCircularTaggedByInterfaceByPhpAttribute(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+ServiceUseOne::__construct()/');

        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Circular\Attributes\ServiceUseOne::class),
            diAutowire(Fixtures\Circular\Attributes\ServiceUseTwo::class),
        ]);

        $class = $container->get(Fixtures\Circular\Attributes\ServiceUse::class);

        $this->assertInstanceOf(Fixtures\Circular\Attributes\ServiceUse::class, $class);

        iterator_to_array($class->services);
    }
}
