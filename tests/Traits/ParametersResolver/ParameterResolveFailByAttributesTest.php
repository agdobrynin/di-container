<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ParameterResolveFailByAttributesTest extends TestCase
{
    use ParametersResolverTrait;
    use DiContainerTrait;

    public function testCannotUseAttributeAsClosureAndInjectTogether(): void
    {
        $fn = static fn (
            #[Inject]
            #[ProxyClosure('someService')]
            $iterator
        ) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->setUseAttribute(true);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use attributes.+together/');

        $this->resolveParameters([], $reflectionParameters);
    }

    public function testCannotUseAttributeTaggedAsAndInjectTogether(): void
    {
        $fn = static fn (
            #[Inject]
            #[TaggedAs('tags.handlers-one')]
            $iterator
        ) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->setUseAttribute(true);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use attributes.+together/');

        $this->resolveParameters([], $reflectionParameters);
    }

    public function testCannotUseAttributeTaggedAsAndInjectAndProxyClosureTogether(): void
    {
        $fn = static fn (
            #[Inject('any.service')]
            #[ProxyClosure('someService')]
            #[TaggedAs('tags.handlers-one')]
            iterable $iterator
        ) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->setUseAttribute(true);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use attributes.+together/');

        $this->resolveParameters([], $reflectionParameters);

    }
}
