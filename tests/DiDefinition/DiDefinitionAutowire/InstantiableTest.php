<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperClassPrivateConstructor;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperInterface;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class InstantiableTest extends TestCase
{
    public function testAutowireIsNotInstantiableStringByInvoke(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Failed reflection for class "'.NotExist::class.'".');

        (new DiDefinitionAutowire(NotExist::class))
            ->setContainer($this->createMock(DiContainerInterface::class))
            ->invoke()
        ;
    }

    public function testAutowireIsNotInstantiableInterfaceByInvoke(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The class "'.SuperInterface::class.'" is not instantiable');

        (new DiDefinitionAutowire(SuperInterface::class))
            ->setContainer($this->createMock(DiContainerInterface::class))
            ->invoke()
        ;
    }

    public function testAutowireIsNotInstantiableWithPrivateConstructorByInvoke(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The class "'.SuperClassPrivateConstructor::class.'" is not instantiable');

        (new DiDefinitionAutowire(SuperClassPrivateConstructor::class))
            ->setContainer($this->createMock(DiContainerInterface::class))
            ->invoke()
        ;
    }
}
