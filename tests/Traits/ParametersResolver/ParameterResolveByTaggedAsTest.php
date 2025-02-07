<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\ParametersResolver\Fixtures\ClassWithDependency;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\tagOptions
 * @covers \Kaspi\DiContainer\Traits\DefinitionIdentifierTrait
 *
 * @internal
 */
class ParameterResolveByTaggedAsTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testResolveByTaggedAsByDiTaggedAsNonVariadic(): void
    {
        $fn = static fn (iterable $item) => $item;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->bindArguments(
            item: diTaggedAs('tags.tag-one'),
        );

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                MoreSuperClass::class => (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                SuperClass::class => (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                ClassWithDependency::class => (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                ClassWithDependency::class,
                MoreSuperClass::class,
            ))
            ->willReturn(
                new ClassWithDependency('ok'),
                new MoreSuperClass(),
            )
        ;
        $mockContainer
            ->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: false))
        ;

        $this->setContainer($mockContainer);

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertTrue($res->valid());
        $this->assertInstanceOf(ClassWithDependency::class, $res->current());

        $res->next();
        $this->assertInstanceOf(MoreSuperClass::class, $res->current());

        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testResolveByTaggedAsByDiTaggedAsVariadic(): void
    {
        $fn = static fn (iterable ...$item) => $item;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->bindArguments(
            item: [
                diTaggedAs('tags.tag-one'),
                diTaggedAs('tags.tag-two'),
            ]
        );

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('getDefinitions')
            ->willReturn([
                MoreSuperClass::class => (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                SuperClass::class => (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                ClassWithDependency::class => (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->expects(self::exactly(3))
            ->method('get')
            ->with(self::logicalOr(
                ClassWithDependency::class,
                MoreSuperClass::class,
                SuperClass::class,
            ))
            ->willReturn(
                new ClassWithDependency('ok'),
                new MoreSuperClass(),
                new SuperClass(),
            )
        ;
        $mockContainer
            ->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: false))
        ;

        $this->setContainer($mockContainer);

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertTrue($res1->valid());
        $this->assertInstanceOf(ClassWithDependency::class, $res1->current());

        $res1->next();
        $this->assertInstanceOf(MoreSuperClass::class, $res1->current());

        $res1->next();
        $this->assertFalse($res1->valid());

        $this->assertTrue($res2->valid());
        $this->assertInstanceOf(SuperClass::class, $res2->current());

        $res2->next();
        $this->assertFalse($res2->valid());
    }

    public function testResolveByTaggedAsByAttributeNonVariadic(): void
    {
        $fn = static fn (#[TaggedAs('tags.tag-one')] iterable $item) => $item;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                MoreSuperClass::class => (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                SuperClass::class => (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                ClassWithDependency::class => (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                ClassWithDependency::class,
                MoreSuperClass::class,
            ))
            ->willReturn(
                new ClassWithDependency('ok'),
                new MoreSuperClass(),
            )
        ;
        $mockContainer
            ->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertTrue($res->valid());
        $this->assertInstanceOf(ClassWithDependency::class, $res->current());

        $res->next();
        $this->assertInstanceOf(MoreSuperClass::class, $res->current());

        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testResolveByTaggedAsByAttributeVariadic(): void
    {
        $fn = static fn (
            #[TaggedAs('tags.tag-one')]
            #[TaggedAs('tags.tag-two')]
            iterable ...$item
        ) => $item;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('getDefinitions')
            ->willReturn([
                MoreSuperClass::class => (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                SuperClass::class => (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                ClassWithDependency::class => (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->expects(self::exactly(3))
            ->method('get')
            ->with(self::logicalOr(
                ClassWithDependency::class,
                MoreSuperClass::class,
                SuperClass::class,
            ))
            ->willReturn(
                new ClassWithDependency('ok'),
                new MoreSuperClass(),
                new SuperClass(),
            )
        ;
        $mockContainer
            ->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertTrue($res1->valid());
        $this->assertInstanceOf(ClassWithDependency::class, $res1->current());

        $res1->next();
        $this->assertInstanceOf(MoreSuperClass::class, $res1->current());

        $res1->next();
        $this->assertFalse($res1->valid());

        $this->assertTrue($res2->valid());
        $this->assertInstanceOf(SuperClass::class, $res2->current());

        $res2->next();
        $this->assertFalse($res2->valid());
    }
}
