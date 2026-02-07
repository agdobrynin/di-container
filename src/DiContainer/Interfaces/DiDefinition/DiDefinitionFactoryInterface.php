<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiDefinitionFactoryInterface extends DiDefinitionSingletonInterface
{
    /**
     * @return array{0: class-string|non-empty-string, 1: non-empty-string}
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function getDefinition(): array;

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
}
