<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait DiDefinitionAutowireInvokeTrait
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    public function invokeAutowireDefinition(DiDefinitionAutowireInterface $definitionAutowire): mixed
    {
        $object = $definitionAutowire->invoke();

        return $object instanceof DiFactoryInterface
            ? $object($definitionAutowire->getContainer())
            : $object;
    }
}
