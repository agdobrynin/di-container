<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    use DiContainerTrait;
    use DefinitionIdentifierTrait;

    /**
     * @param non-empty-string $tag
     */
    public function __construct(private string $tag, private bool $lazy = true) {}

    /**
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(): iterable
    {
        $taggedServices = [];

        foreach ($this->getContainer()->getDefinitions() as $id => $definition) {
            if ($definition instanceof DiTaggedDefinitionInterface && $definition->hasTag($this->getDefinition())) {
                $taggedServices[$id] = $definition;
            }
        }

        // Operation through tag options
        if ([] !== $taggedServices) {
            $tag = $this->getDefinition();
            // sorting by priority key in options
            \uasort(
                $taggedServices,
                static fn (DiTaggedDefinitionInterface $a, DiTaggedDefinitionInterface $b) => $a->getOptionPriority($tag) <=> $b->getOptionPriority($tag)
            );
        }

        if (!$this->lazy) {
            $services = [];

            foreach ($taggedServices as $id => $taggedDefinition) {
                $services[] = $this->getContainer()->get($this->tryGetIdentifier($id, $taggedDefinition));
            }

            yield from $services;
        } else {
            foreach ($taggedServices as $id => $taggedDefinition) {
                yield $this->getContainer()->get($this->tryGetIdentifier($id, $taggedDefinition));
            }
        }
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @throws DiDefinitionException
     */
    private function tryGetIdentifier(mixed $id, mixed $definition): string
    {
        return $this->getIdentifier($id, $definition);
    }
}
