<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Iterator;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\IdsIteratorInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerExceptionInterface;

use function sprintf;

final class DiContainerDefinitions implements DiContainerDefinitionsInterface
{
    public function __construct(private readonly DiContainerInterface $container, private readonly IdsIteratorInterface $idsIterator) {}

    public function getContainer(): DiContainerInterface
    {
        return $this->container;
    }

    public function isSingletonDefinitionDefault(): bool
    {
        return $this->container->getConfig()->isSingletonServiceDefault();
    }

    public function getDefinitions(): Iterator
    {
        $sentContainerIdentifiers = [];

        foreach ($this->container->getDefinitions() as $id => $definition) {
            $sentContainerIdentifiers[$id] = true;

            yield $id => $definition;
        }

        while (false !== ($id = $this->idsIterator->current())) {
            if (!isset($sentContainerIdentifiers[$id])) {
                try {
                    yield $id => $this->container->getDefinition($id);
                } catch (ContainerExceptionInterface $e) {
                    throw new DefinitionCompileException(
                        sprintf('Cannot get definition via container identifier "%s".', $id),
                        previous: $e
                    );
                }
            }

            $this->idsIterator->next();
        }
    }

    public function pushToDefinitionIterator(string $containerIdentifier): void
    {
        $this->idsIterator->add($containerIdentifier);
    }
}
