<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;

interface DiTaggedDefinitionAutowireInterface extends DiTaggedDefinitionInterface
{
    /**
     * @throws AutowireExceptionInterface
     */
    public function getDefinition(): \ReflectionClass;

    public function setContainer(DiContainerInterface $container): static;
}
