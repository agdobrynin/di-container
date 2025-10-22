<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ParameterResolveFailByAttributesTest extends TestCase
{
    use ParametersResolverTrait;
    use DiContainerTrait;

    private ?object $mockContainer = null;

    public function setUp(): void
    {
        $this->mockContainer = $this->createMock(DiContainerInterface::class);
        $this->mockContainer->expects(self::once())
            ->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;
    }

    public function tearDown(): void
    {
        $this->mockContainer = null;
    }

    public function testCannotUseAttributeAsClosureAndInjectTogether(): void
    {
        $fn = static fn (
            #[Inject]
            #[ProxyClosure('someService')]
            $iterator
        ) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $this->setContainer($this->mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Only one of the attributes.+may be declared/');

        $this->resolveParameters([], $reflectionParameters, true);
    }

    public function testCannotUseAttributeTaggedAsAndInjectTogether(): void
    {
        $fn = static fn (
            #[Inject]
            #[TaggedAs('tags.handlers-one')]
            $iterator
        ) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $this->setContainer($this->mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Only one of the attributes.+may be declared/');

        $this->resolveParameters([], $reflectionParameters, true);
    }

    public function testCannotUseAttributeTaggedAsAndInjectAndProxyClosureTogether(): void
    {
        $fn = static fn (
            #[Inject('any.service')]
            #[ProxyClosure('someService')]
            #[TaggedAs('tags.handlers-one')]
            #[InjectByCallable('func')]
            iterable $iterator
        ) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $this->setContainer($this->mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Only one of the attributes.+may be declared/');

        $this->resolveParameters([], $reflectionParameters, true);
    }

    public function testCannotUseAttributeInjectAndInjectCallableTogether(): void
    {
        $fn = static fn (
            #[Inject('any.service')]
            #[InjectByCallable('func')]
            iterable $iterator
        ) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $this->setContainer($this->mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Only one of the attributes.+may be declared/');

        $this->resolveParameters([], $reflectionParameters, true);
    }
}
