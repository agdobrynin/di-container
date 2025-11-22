<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperClassPrivateConstructor;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperInterface;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
 *
 * @internal
 */
class InstantiableTest extends TestCase
{
    public function testAutowireIsNotInstantiableStringByInvoke(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Class "'.NotExist::class.'" does not exist');

        (new DiDefinitionAutowire(NotExist::class))
            ->resolve($this->createMock(DiContainerInterface::class))
        ;
    }

    public function testAutowireIsNotInstantiableInterfaceByInvoke(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/SuperInterface.+class is not instantiable/');

        (new DiDefinitionAutowire(SuperInterface::class))
            ->resolve($this->createMock(DiContainerInterface::class))
        ;
    }

    public function testAutowireIsNotInstantiableWithPrivateConstructorByInvoke(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/SuperClassPrivateConstructor.+class is not instantiable/');

        (new DiDefinitionAutowire(SuperClassPrivateConstructor::class))
            ->resolve($this->createMock(DiContainerInterface::class))
        ;
    }
}
