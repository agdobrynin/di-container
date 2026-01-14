<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\IterableArg;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleA;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleInterface;

use function func_get_args;
use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $definitions = [
            'services.rule-list' => diCallable(
                definition: static fn (RuleA $a, RuleB $b) => func_get_args(),
                isSingleton: true
            ),
        ];

        $container = new DiContainer(
            $definitions,
            new DiContainerConfig(
                useAttribute: true // use attributes
            )
        );

        $class = $container->get(IterableArg::class);

        foreach ($class->getValues() as $item) {
            $this->assertInstanceOf(RuleInterface::class, $item);
            $this->assertContains($item::class, [RuleA::class, RuleB::class]);
        }
    }
}
