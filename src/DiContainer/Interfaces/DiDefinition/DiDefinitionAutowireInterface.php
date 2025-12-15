<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

interface DiDefinitionAutowireInterface extends DiTaggedDefinitionInterface
{
    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function getDefinition(): ReflectionClass;

    public function setContainer(DiContainerInterface $container): static;

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string;

    /**
     * @throws ArgumentBuilderExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): object;

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
