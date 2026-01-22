<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Tag::class)]
#[CoversClass(TaggedAs::class)]
#[CoversClass(DefinitionDiCall::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(Helper::class)]
#[CoversClass(LazyDefinitionIterator::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class KeyThroughContainerAsPhpAttributesTest extends TestCase
{
    public function testNotLazyKeyAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Attributes\One::class),
            diAutowire(Fixtures\Attributes\Two::class),
            diAutowire(Fixtures\Attributes\Three::class),
        ]);

        // 'tags.one'
        $class = $container->get(Fixtures\Attributes\TaggedServiceAsArray::class);

        $this->assertIsArray($class->items);
        $this->assertCount(2, $class->items);

        $this->assertInstanceOf(Fixtures\Attributes\One::class, $class->items['some_service.one-other']);
        $this->assertInstanceOf(Fixtures\Attributes\Two::class, $class->items['some_service.Dos']);
    }

    public function testLazyKeyAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Attributes\One::class),
            diAutowire(Fixtures\Attributes\Two::class),
            diAutowire(Fixtures\Attributes\Three::class),
        ]);

        // 'tags.one'
        $class = $container->get(Fixtures\Attributes\TaggedServiceAsLazy::class);

        $this->assertIsIterable($class->items);
        $this->assertCount(2, $class->items);
        $this->assertEquals(2, $class->items->count());

        $this->assertInstanceOf(Fixtures\Attributes\One::class, $class->items['some_service.one-other']);
        $this->assertInstanceOf(Fixtures\Attributes\One::class, $class->items->get('some_service.one-other'));

        $this->assertInstanceOf(Fixtures\Attributes\Two::class, $class->items['some_service.Dos']);
        $this->assertInstanceOf(Fixtures\Attributes\Two::class, $class->items->get('some_service.Dos'));
    }

    public function testLazyGetKeyByMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixtures\Attributes\One::class),
            diAutowire(Fixtures\Attributes\Two::class),
            diAutowire(Fixtures\Attributes\Three::class),
        ]);

        $res = $container->call([Fixtures\Attributes\TaggedServiceAsLazy::class, 'getKeyByMethod']);

        $this->assertInstanceOf(Fixtures\Attributes\Three::class, $res->get('signed_service'));
    }
}
