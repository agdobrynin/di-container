<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\TaggedAs;
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
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ClassWithTaggedArg;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\MainClass;

use function current;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(DiContainer::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(TaggedAs::class)]
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
class TaggedAsTest extends TestCase
{
    public function testTaggedAsThroughContainerWithoutAttributes(): void
    {
        $definitions = [
            diAutowire(ClassWithTaggedArg::class)
                ->bindArguments(tagged: diTaggedAs('tags.callable-handlers', false)),
            'someName1' => diCallable([MainClass::class, 'imStatic'])
                ->bindArguments('ola!')
                ->bindTag('tags.callable-handlers'),
            diAutowire(MainClass::class)
                ->bindArguments(serviceName: 'SuperServiceHere'),
        ];
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $res = $container->get(ClassWithTaggedArg::class);

        $this->assertCount(1, $res->tagged);
        $this->assertEquals('❤ola!', current($res->tagged));
        // key of tagged service
        $this->assertEquals('❤ola!', $res->tagged['someName1']);
    }

    public function testTaggedAsThroughContainerByAttributes(): void
    {
        $definitions = [
            'someName1' => diCallable([MainClass::class, 'imStatic'])
                ->bindArguments('ola!')
                ->bindTag('tags.callable-handlers'),
            diAutowire(MainClass::class)
                ->bindArguments(serviceName: 'SuperServiceHere'),
        ];

        $container = new DiContainer($definitions, new DiContainerConfig(useAttribute: true));

        $res = $container->get(ClassWithTaggedArg::class);

        $this->assertEquals('❤ola!', $res->tagged->current());
        $this->assertEquals('someName1', $res->tagged->key());

        $res->tagged->next();
        $this->assertFalse($res->tagged->valid());
    }
}
