<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
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
        $this->isInterface ??= \interface_exists($this->getDefinition());
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
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getTaggedByTag(): \Generator
    {
        $taggedServices = [];
        $tag = $this->getDefinition();

        foreach ($this->getContainer()->getDefinitions() as $id => $definition) {
            if ($definition instanceof DiTaggedDefinitionInterface && $definition->hasTag($tag)) {
                $taggedServices[$id] = $definition;
            }
        }

        // Operation through tag options
        /*
         * 🚩 Sorting by 'priority' key in tag options.
         * Tag with higher number in 'priority' key being early in list.
         */
        \uasort(
            $taggedServices,
            static fn (DiTaggedDefinitionInterface $a, DiTaggedDefinitionInterface $b) => $b->getOptionPriority($tag) <=> $a->getOptionPriority($tag)
        );

        yield from $taggedServices;
    }

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getTaggedByInterface(): \Generator
    {
        $tag = $this->getDefinition();

        foreach ($this->getContainer()->getDefinitions() as $id => $definition) {
            try {
                if ($definition instanceof DiDefinitionAutowire && $definition->getDefinition()->implementsInterface($tag)) {
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
