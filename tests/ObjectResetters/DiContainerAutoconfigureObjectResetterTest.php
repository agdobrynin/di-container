<?php

declare(strict_types=1);

namespace Tests\ObjectResetters;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\ObjectResettersInterface;
use Kaspi\DiContainer\ObjectResetters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\SourceDefinitionItem;
use Kaspi\DiContainer\Traits\FreezeTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\ObjectResetters\Fixtures\Foo;
use Tests\ObjectResetters\Fixtures\FooResetter;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diRuntime;

/**
 * @internal
 */
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionItem::class)]
#[CoversClass(TagsTrait::class)]
#[CoversClass(FreezeTrait::class)]
#[CoversClass(ObjectResetters::class)]
#[CoversClass(NotFoundException::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversFunction('Kaspi\DiContainer\diRuntime')]
class DiContainerAutoconfigureObjectResetterTest extends TestCase
{
    public function testManuallyConfigure(): void
    {
        $container = new DiContainer(
            [
                diAutowire(ObjectResetters::class),
            ],
            config: new DiContainerConfig(isConfigureObjectResettersFromDefinitions: true)
        );

        self::assertTrue($container->has(ObjectResetters::class));

        $definitions = [...$container->getDefinitions()];

        self::assertCount(1, $definitions);
        self::assertArrayHasKey(ObjectResetters::class, $definitions);
    }

    #[TestWith([ObjectResettersInterface::class])]
    #[TestWith([ObjectResetters::class])]
    public function testAutoconfigureObjectResettersOff(string $entryId): void
    {
        $container = new DiContainer(
            [
                diAutowire(Foo::class),
            ],
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: true,
                isConfigureObjectResettersFromDefinitions: false
            )
        );

        self::assertTrue($container->has(Foo::class));
        self::assertFalse($container->has($entryId));

        $definitions = [...$container->getDefinitions()];

        self::assertArrayNotHasKey($entryId, $definitions);
        self::assertArrayHasKey(Foo::class, $definitions);

        $this->expectException(NotFoundExceptionInterface::class);
        $container->getDefinition($entryId);
    }

    public function testGetObjectResettersAutoconfiguredByDiContainerForDiAutowire(): void
    {
        $container = new DiContainer(
            [
                diAutowire(Foo::class, true)
                    ->setResetter('flush'),
            ],
            config: new DiContainerConfig(isConfigureObjectResettersFromDefinitions: true),
        );

        $container->get(Foo::class)->foo = 'bar';

        self::assertEquals('bar', $container->get(Foo::class)->foo);

        foreach ([ObjectResetters::class, ObjectResettersInterface::class] as $objectResettersEntryId) {
            $resetter = $container->get($objectResettersEntryId);
            $resetter->reset();

            self::assertEquals('null', $container->get(Foo::class)->foo);

            $container->get(Foo::class)->foo = 'bar';

            self::assertEquals('bar', $container->get(Foo::class)->foo);
        }
    }

    public function testGetObjectResettersAutoconfiguredByDiContainerForDiRuntime(): void
    {
        $container = new DiContainer(
            [
                diRuntime(Foo::class)
                    ->setResetter(FooResetter::class.'::doReset'),
            ],
            config: new DiContainerConfig(isConfigureObjectResettersFromDefinitions: true),
        );

        $foo = new Foo();
        $foo->foo = 'bar';

        $container->set(Foo::class, $foo);

        $container->get(Foo::class)->foo = 'bar';

        self::assertEquals('bar', $container->get(Foo::class)->foo);

        $resetter = $container->get(ObjectResetters::class);
        $resetter->reset();

        self::assertEquals('foo', $container->get(Foo::class)->foo);
    }
}
