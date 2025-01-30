<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    use DiContainerTrait;
    use DefinitionIdentifierTrait;

    private bool $isInterface;

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
        $this->isInterface ??= \interface_exists($this->tag);
        $taggedServices = $this->isInterface ? $this->getTaggedByInterface() : $this->getTaggedByTag();

        if (!$taggedServices->valid()) {
            return $this->lazy
                ? (static function () { yield from []; })()
                : [];
        }

        if (!$this->lazy) {
            $services = [];

            foreach ($taggedServices as $id => $taggedDefinition) {
                $services[] = $this->getContainer()->get($this->tryGetIdentifier($id, $taggedDefinition));
            }

            return $services;
        }

        return $this->getServicesAsLazy($taggedServices);
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getServicesAsLazy(iterable $taggedServices): \Generator
    {
        foreach ($taggedServices as $id => $taggedDefinition) {
            yield $this->getContainer()->get($this->tryGetIdentifier($id, $taggedDefinition));
        }
    }

    /**
     * @return \Generator<non-empty-string, DiDefinitionInterface>
     *
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getTaggedByTag(): \Generator
    {
        $taggedServices = new \SplPriorityQueue();
        $taggedServices->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

        foreach ($this->getContainer()->getDefinitions() as $id => $definition) {
            if ($definition instanceof DiTaggedDefinitionInterface
                && $definition->hasTag($this->tag)) {
                // 🚩 Tag with higher number in 'priority' key being early in list.
                $taggedServices->insert([$id, $definition], $definition->getOptionPriority($this->tag));
            }
        }

        foreach ($taggedServices as $taggedDefinition) {
            [$id, $definition] = $taggedDefinition;

            yield $id => $definition;
        }
    }

    /**
     * @return \Generator<non-empty-string, DiDefinitionAutowire>
     *
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getTaggedByInterface(): \Generator
    {
        foreach ($this->getContainer()->getDefinitions() as $id => $definition) {
            try {
                if ($definition instanceof DiDefinitionAutowire
                    && $definition->getDefinition()->implementsInterface($this->tag)) {
                    yield $id => $definition;
                }
            } catch (AutowireExceptionInterface $e) {
                throw new ContainerException(message: $e->getMessage(), previous: $e);
            }
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
