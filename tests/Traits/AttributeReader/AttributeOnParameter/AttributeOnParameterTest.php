<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\AttributeOnParameter;

use Generator;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class AttributeOnParameterTest extends TestCase
{
    use AttributeReaderTrait;

    /**
     * @dataProvider dataProviderParam
     */
    public function testAttributeOnParameterIntersect(ReflectionParameter $param): void
    {
        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessageMatches('/Only one of the attributes.+at Parameter #0.+[ <required> \$param ].+AttributeOnParameterTest::.+()/');

        $this->getAttributeOnParameter(
            $param,
            $this->createMock(DiContainerInterface::class)
        )->valid();
    }

    public function dataProviderParam(): Generator
    {
        yield 'Inject and ProxyClosure' => [
            (new ReflectionFunction(static fn (#[Inject, ProxyClosure('service.one')] $param) => true))->getParameters()[0],
        ];

        yield 'ProxyClosure and TaggedAs' => [
            (new ReflectionFunction(static fn (#[ProxyClosure('service.one'), TaggedAs('tags.one')] $param) => true))->getParameters()[0],
        ];

        yield 'InjectByCallable and TaggedAs' => [
            (new ReflectionFunction(static fn (#[TaggedAs('tags.one'), InjectByCallable('func2')] $param) => true))->getParameters()[0],
        ];
    }
}
