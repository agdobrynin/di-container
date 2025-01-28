<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\TaggedAs;
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
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Traits\DefinitionIdentifierTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
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
        $mockContainer->method('getDefinitions')
            ->willReturn([
                (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->method('get')
            ->with(self::logicalOr(
                ClassWithDependency::class,
                MoreSuperClass::class,
            ))
            ->willReturn(
                new ClassWithDependency('ok'),
                new MoreSuperClass(),
            )
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(false);

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
        $mockContainer->method('getDefinitions')
            ->willReturn([
                (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->method('get')
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
        $this->setContainer($mockContainer);
        $this->setUseAttribute(false);

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
        $mockContainer->method('getDefinitions')
            ->willReturn([
                (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->method('get')
            ->with(self::logicalOr(
                ClassWithDependency::class,
                MoreSuperClass::class,
            ))
            ->willReturn(
                new ClassWithDependency('ok'),
                new MoreSuperClass(),
            )
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

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
        $mockContainer->method('getDefinitions')
            ->willReturn([
                (new DiDefinitionAutowire(MoreSuperClass::class))
                    ->bindTag('tags.tag-one'),
                (new DiDefinitionAutowire(SuperClass::class))
                    ->bindTag('tags.tag-two'),
                (new DiDefinitionAutowire(ClassWithDependency::class))
                    ->bindTag('tags.tag-one', ['priority' => 100]),
            ])
        ;
        $mockContainer->method('get')
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
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

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
