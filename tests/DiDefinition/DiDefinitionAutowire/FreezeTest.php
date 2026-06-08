<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\FreezeTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperClass;

/**
 * @internal
 */
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(FreezeTrait::class)]
#[CoversClass(TagsTrait::class)]
class FreezeTest extends TestCase
{
    private DiContainerInterface $container;
    private DiDefinitionAutowire $definition;

    protected function setUp(): void
    {
        $this->container = $this->createMock(DiContainerInterface::class);
        $this->definition = (new DiDefinitionAutowire(SuperClass::class))->setContainer($this->container);
    }

    protected function tearDown(): void
    {
        unset($this->container, $this->definition);
    }

    public function testFreezeBindArgs(): void
    {
        $this->definition->bindArguments('foo');
        self::assertEquals([0 => 'foo'], $this->definition->exposeArgumentBuilder($this->container)->getBindArguments());

        $this->definition->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::bindArguments() on a frozen definition.');

        $this->definition->bindArguments('bar');
    }

    public function testFreezeSetContainerIdentifier(): void
    {
        $this->definition->setContainerIdentifier('service.foo');
        self::assertEquals('service.foo', $this->definition->getContainerIdentifier());

        $this->definition->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::setContainerIdentifier() on a frozen definition.');

        $this->definition->setContainerIdentifier('service.bar');
    }

    public function testFreezeSetupImmutable(): void
    {
        $this->definition->setupImmutable('withDependency', ['bar']);

        /** @var list<array{0: SetupConfigureMethod, 1: ArgumentBuilderInterface}> $setups */
        $setups = $this->definition->exposeSetupArgumentBuilders($this->container);

        self::assertCount(1, $setups);
        self::assertEquals(SetupConfigureMethod::Immutable, $setups[0][0]);
        self::assertEquals([0 => 'bar'], $setups[0][1]->getBindArguments());

        $this->definition->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::setupImmutable() on a frozen definition.');

        $this->definition->setupImmutable('withDependency', ['baz']);
    }

    public function testFreezeSetup(): void
    {
        $this->definition->setup('setDependency', ['bar']);

        /** @var list<array{0: SetupConfigureMethod, 1: ArgumentBuilderInterface}> $setups */
        $setups = $this->definition->exposeSetupArgumentBuilders($this->container);

        self::assertCount(1, $setups);
        self::assertEquals(SetupConfigureMethod::Mutable, $setups[0][0]);
        self::assertEquals([0 => 'bar'], $setups[0][1]->getBindArguments());

        $this->definition->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::setup() on a frozen definition.');

        $this->definition->setup('setDependency', ['baz']);
    }

    public function testBindTag(): void
    {
        self::assertEquals([], $this->definition->getTags());

        $this->definition->bindTag('tags.foo');

        self::assertEquals(['tags.foo' => []], $this->definition->getTags());

        $this->definition->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire::bindTag() on a frozen definition.');

        $this->definition->bindTag('tags.bar');
    }
}
