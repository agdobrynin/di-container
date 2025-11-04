<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_map;

trait ArgumentResolverTrait
{
    use DiContainerTrait;
    use ParameterTypeByReflectionTrait;

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @param array<non-empty-string|non-negative-int, mixed> $inputArguments
     *
     * @return array<int|string, mixed>
     *
     * @throws AutowireAttributeException
     * @throws AutowireExceptionInterface
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function resolveArguments(array $inputArguments): array
    {
        return array_map(function ($argument) {
            return $this->resolveInputArgument($argument);
        }, $inputArguments);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AutowireExceptionInterface
     */
    private function resolveInputArgument(mixed $argumentDefinition): mixed
    {
        if ($argumentDefinition instanceof DiDefinitionLinkInterface) {
            return $this->getContainer()->get($argumentDefinition->getDefinition());
        }

        if ($argumentDefinition instanceof DiDefinitionTaggedAsInterface) {
            if ($this instanceof DiDefinitionAutowireInterface) {
                $argumentDefinition->setCallingByService($this);
            }

            return $argumentDefinition->setContainer($this->getContainer())
                ->getServicesTaggedAs()
            ;
        }

        if ($argumentDefinition instanceof DiDefinitionInvokableInterface) {
            $o = $argumentDefinition->setContainer($this->getContainer())->invoke();

            return $o instanceof DiFactoryInterface
                ? $o($this->getContainer())
                : $o;
        }

        if ($argumentDefinition instanceof DiDefinitionInterface) {
            return $argumentDefinition->getDefinition();
        }

        return $argumentDefinition;
    }
}
