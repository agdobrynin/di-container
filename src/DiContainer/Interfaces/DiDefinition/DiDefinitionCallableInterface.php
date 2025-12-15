<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiDefinitionCallableInterface extends DiDefinitionInterface
{
    public function getDefinition(): callable;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeArgumentBuilder(DiContainerInterface $container): ArgumentBuilderInterface;
}
