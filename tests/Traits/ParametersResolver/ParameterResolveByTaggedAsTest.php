<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;

use function Kaspi\DiContainer\diTaggedAs;

/**
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

        //        $mockContainer = $this->createMock(DiContainerInterface::class);
        //        $mockContainer->method('has')
        //            ->with('tags.services.voters')
        //            ->willReturn(false)
        //        ;
        //        $mockContainer->method('get')
        //            ->with(MoreSuperClass::class)
        //            ->willReturn(new MoreSuperClass())
        //        ;
        //
        //        $this->setContainer($mockContainer);

        $this->bindArguments(
            item: diTaggedAs('tags.services.voters'),
        );

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));
        \var_dump($res->valid());
    }
}
