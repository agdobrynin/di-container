<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Unit\Attribute\Inject\Fixtures\MethodWithInjectByReferenceNotFound;
use Tests\Unit\Attribute\Inject\Fixtures\MethodWithNonVariadicParameterInjectManyTimes;
use Tests\Unit\Attribute\Inject\Fixtures\MethodWithVariadicParameterInjectByClass;
use Tests\Unit\Attribute\Inject\Fixtures\RuleA;
use Tests\Unit\Attribute\Inject\Fixtures\RuleB;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\Attributes\InjectByReference
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class InjectWithCallMethodTest extends TestCase
{
    public function testInjectMethodByClassName(): void
    {
        $container = (new DiContainerFactory())->make();

        $res = $container->call([MethodWithVariadicParameterInjectByClass::class, 'rulesInvoke'], ['exclude' => 'uri']);

        $this->assertInstanceOf(RuleB::class, $res[0]);
        $this->assertEquals('address', $res[0]->rule);

        $this->assertInstanceOf(RuleA::class, $res[1]);
        $this->assertEquals('zip', $res[1]->rule);

        $this->assertEquals('uri', $res['exclude']);
    }

    public function testInjectMethodNonVariadicParameterWithManyInject(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        $container->call([MethodWithNonVariadicParameterInjectManyTimes::class, 'rulesInvoke']);
    }

    public function testInjectMethodInjectNotFound(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+rules.text.strip_tags/');

        $container->call([MethodWithInjectByReferenceNotFound::class, 'rulesInvoke']);
    }
}
