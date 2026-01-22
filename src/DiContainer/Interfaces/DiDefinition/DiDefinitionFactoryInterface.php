<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiDefinitionFactoryInterface extends DiDefinitionSingletonInterface
{
    /**
     * @return class-string
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function getDefinition(): string;

    /**
     * @return non-empty-string
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function getFactoryMethod(): string;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeFactoryMethodArgumentBuilder(DiContainerInterface $container): ArgumentBuilderInterface;

    public function getFactoryAutowire(): DiDefinitionAutowireInterface;
}
