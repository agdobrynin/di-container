<?php

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\AsClosure;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\AsClosure
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ParameterResolveByAttributesTest extends TestCase
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    public function testCannotUseAttributeAsClosureAndInjectTogether(): void
    {
        $fn = static fn (
            #[Inject]
            #[AsClosure('someService')]
            $iterator
        ) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->setUseAttribute(true);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use attributes .+Inject, .+AsClosure together/');

        $this->resolveParameters();
    }
}
