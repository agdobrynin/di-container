<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionFactoryInterface
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
     * @throws ArgumentBuilderExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): mixed;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeArgumentBuilder(DiContainerInterface $container): ?ArgumentBuilderInterface;

    /**
     * @return list<array{0: DiDefinitionSetupConfigureInterface, 1: ArgumentBuilderInterface}>
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeSetupArgumentBuilders(DiContainerInterface $container): array;
}
