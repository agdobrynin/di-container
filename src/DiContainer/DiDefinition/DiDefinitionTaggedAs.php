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

        if ([] === $taggedServices) {
            return $this->lazy
                ? (static function () { yield from []; })()
                : [];
        }

        // Operation through tag options
        $tag = $this->getDefinition();

        /*
         * 🚩 Sorting by 'priority' key in tag options.
         * Tag with higher number in 'priority' key being early in list.
         */
        \uasort(
            $taggedServices,
            static fn (DiTaggedDefinitionInterface $a, DiTaggedDefinitionInterface $b) => $b->getOptionPriority($tag) <=> $a->getOptionPriority($tag)
        );

        if (!$this->lazy) {
            $services = [];

            foreach ($taggedServices as $id => $taggedDefinition) {
                $services[] = $this->getContainer()->get($this->tryGetIdentifier($id, $taggedDefinition));
            }

            return $services;
        }

        return $this->getTaggedServicesLazy($taggedServices);
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    private function getTaggedServicesLazy(array $taggedServices): \Generator
    {
        foreach ($taggedServices as $id => $taggedDefinition) {
            yield $this->getContainer()->get($this->tryGetIdentifier($id, $taggedDefinition));
        }
    }

    /**
     * @throws DiDefinitionException
     */
    private function tryGetIdentifier(mixed $id, mixed $definition): string
    {
        return $this->getIdentifier($id, $definition);
    }
}
